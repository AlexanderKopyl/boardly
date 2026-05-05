<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Bus\CommandBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class RefreshAuthenticationControllerTest extends WebTestCase
{
    private const string PLAIN_PASSWORD = 'Password123!';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AccountRepositoryInterface $accounts;
    private PasswordHasherInterface $passwordHasher;
    private RefreshTokenHasherInterface $refreshTokenHasher;
    private RefreshSessionRepositoryInterface $refreshSessions;

    protected function setUp(): void
    {
        $this->setRequiredTestSecrets();

        $this->client = self::createClient();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->accounts = $container->get(AccountRepositoryInterface::class);
        $this->passwordHasher = $container->get(PasswordHasherInterface::class);
        $this->refreshTokenHasher = $container->get(RefreshTokenHasherInterface::class);
        $this->refreshSessions = $container->get(RefreshSessionRepositoryInterface::class);

        self::assertTrue(
            $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['accounts', 'refresh_sessions']),
            'The accounts and refresh_sessions tables must exist. Run doctrine:migrations:migrate --env=test before this test.',
        );

        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM refresh_sessions');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM accounts');
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testValidCookieReturns200(): void
    {
        $account = $this->persistAccount('refresh-ok@example.com', 'Refresh OK', 'active');
        $this->persistRefreshSession($account, 'old-refresh-token');

        $this->postRefresh('old-refresh-token');

        self::assertResponseStatusCodeSame(200);
    }

    public function testValidCookieReturnsAccessTokenResponseWithoutSensitiveFields(): void
    {
        $account = $this->persistAccount('refresh-response@example.com', 'Refresh Response', 'active');
        $this->persistRefreshSession($account, 'old-refresh-token');

        $this->postRefresh('old-refresh-token');

        $data = $this->responseData();
        $json = (string) $this->client->getResponse()->getContent();

        self::assertSame(['accessToken', 'tokenType', 'expiresIn'], array_keys($data));
        self::assertIsString($data['accessToken']);
        self::assertNotEmpty($data['accessToken']);
        self::assertSame('Bearer', $data['tokenType']);
        self::assertSame(900, $data['expiresIn']);
        self::assertArrayNotHasKey('refreshToken', $data);
        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('passwordHash', $data);
        self::assertArrayNotHasKey('refreshTokenHash', $data);
        self::assertStringNotContainsString('refreshToken', $json);
        self::assertStringNotContainsString('password', $json);
        self::assertStringNotContainsString('passwordHash', $json);
        self::assertStringNotContainsString('refreshTokenHash', $json);
    }

    public function testValidCookieSetsNewRefreshCookieWithExpectedAttributes(): void
    {
        $account = $this->persistAccount('refresh-cookie@example.com', 'Refresh Cookie', 'active');
        $this->persistRefreshSession($account, 'old-refresh-token');

        $this->postRefresh('old-refresh-token');

        $cookie = $this->refreshCookie();
        $storedHashes = $this->storedRefreshTokenHashes($account);

        self::assertSame('refresh_token', $cookie->getName());
        self::assertNotEmpty($cookie->getValue());
        self::assertNotSame('old-refresh-token', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/api/auth', $cookie->getPath());
        self::assertGreaterThan(time(), $cookie->getExpiresTime());

        $setCookieHeader = strtolower($this->setCookieHeader());
        self::assertStringContainsString('refresh_token=', $setCookieHeader);
        self::assertStringContainsString('httponly', $setCookieHeader);
        self::assertStringContainsString('secure', $setCookieHeader);
        self::assertStringContainsString('samesite=lax', $setCookieHeader);
        self::assertStringContainsString('path=/api/auth', $setCookieHeader);

        foreach ($storedHashes as $storedHash) {
            self::assertNotSame($cookie->getValue(), $storedHash);
        }
    }

    public function testOldRefreshTokenCannotBeReusedAfterRotation(): void
    {
        $account = $this->persistAccount('refresh-reuse@example.com', 'Refresh Reuse', 'active');
        $this->persistRefreshSession($account, 'old-refresh-token');

        $this->postRefresh('old-refresh-token');
        self::assertResponseStatusCodeSame(200);

        $this->postRefresh('old-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testMissingCookieReturns401AndClearsCookie(): void
    {
        $this->postRefresh();

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testUnknownTokenReturns401AndClearsCookie(): void
    {
        $this->postRefresh('unknown-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testExpiredTokenReturns401AndClearsCookie(): void
    {
        $account = $this->persistAccount('expired-refresh@example.com', 'Expired Refresh', 'active');
        $this->persistRefreshSession(
            $account,
            'expired-refresh-token',
            expiresAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );

        $this->postRefresh('expired-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testRevokedTokenReturns401AndClearsCookie(): void
    {
        $account = $this->persistAccount('revoked-refresh@example.com', 'Revoked Refresh', 'active');
        $this->persistRefreshSession(
            $account,
            'revoked-refresh-token',
            revokedAt: new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
        );

        $this->postRefresh('revoked-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testReplacedTokenReuseReturns401ClearsCookieAndRevokesFamily(): void
    {
        $account = $this->persistAccount('replaced-refresh@example.com', 'Replaced Refresh', 'active');
        $familyId = RefreshSessionFamilyId::fromString(Uuid::v7()->toRfc4122());
        $this->persistRefreshSession(
            $account,
            'replaced-refresh-token',
            familyId: $familyId,
            replacedByTokenId: RefreshSessionId::fromString(Uuid::v7()->toRfc4122()),
        );
        $this->persistRefreshSession($account, 'current-refresh-token', familyId: $familyId);

        $this->postRefresh('replaced-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
        self::assertSame(2, $this->revokedFamilySessionCount($familyId));
    }

    public function testNonActiveAccountReturns401AndClearsCookie(): void
    {
        $account = $this->persistAccount('pending-refresh@example.com', 'Pending Refresh', 'pending_approval');
        $this->persistRefreshSession($account, 'pending-refresh-token');

        $this->postRefresh('pending-refresh-token');

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidRefreshTokenError();
        $this->assertClearsRefreshCookie();
    }

    public function testControllerUsesCommandBusInterface(): void
    {
        $constructor = new \ReflectionMethod(
            \App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\RefreshAuthenticationController::class,
            '__construct',
        );
        $parameterType = $constructor->getParameters()[0]->getType();

        self::assertInstanceOf(\ReflectionNamedType::class, $parameterType);
        self::assertSame(CommandBusInterface::class, $parameterType->getName());
    }

    private function setRequiredTestSecrets(): void
    {
        $_ENV['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_SERVER['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_ENV['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
        $_SERVER['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
    }

    private function postRefresh(?string $rawRefreshToken = null): void
    {
        $this->client->getCookieJar()->clear();

        if (null !== $rawRefreshToken) {
            $this->client->getCookieJar()->set(new BrowserCookie(
                'refresh_token',
                $rawRefreshToken,
                0,
                '/api/auth',
                '',
                true,
                true,
                false,
                Cookie::SAMESITE_LAX,
            ));
        }

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'Boardly HTTP test',
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTPS' => 'on',
            ],
        );
    }

    /** @return array<string, mixed> */
    private function responseData(): array
    {
        $data = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($data);

        return $data;
    }

    private function persistAccount(string $email, string $name, string $status): Account
    {
        $createdAt = new \DateTimeImmutable('2026-05-04T12:00:00+00:00');
        $account = Account::register(
            AccountId::fromString(Uuid::v7()->toRfc4122()),
            Email::fromString($email),
            PasswordHash::fromString($this->passwordHasher->hash(self::PLAIN_PASSWORD)),
            AccountName::fromString($name),
            $createdAt,
        )->account();

        match ($status) {
            'active' => $account->approve($createdAt->modify('+1 minute')),
            'rejected' => $account->reject($createdAt->modify('+1 minute')),
            'disabled' => $this->disable($account, $createdAt),
            'pending_approval' => null,
            default => throw new \InvalidArgumentException(sprintf('Unsupported account status "%s".', $status)),
        };

        $this->accounts->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $account;
    }

    private function disable(Account $account, \DateTimeImmutable $createdAt): void
    {
        $account->approve($createdAt->modify('+1 minute'));
        $account->disable($createdAt->modify('+2 minutes'));
    }

    private function persistRefreshSession(
        Account $account,
        string $rawRefreshToken,
        ?\DateTimeImmutable $expiresAt = null,
        ?\DateTimeImmutable $revokedAt = null,
        ?RefreshSessionFamilyId $familyId = null,
        ?RefreshSessionId $replacedByTokenId = null,
    ): RefreshSession {
        $createdAt = new \DateTimeImmutable('2026-05-04T12:00:00+00:00');
        $session = RefreshSession::create(
            RefreshSessionId::fromString(Uuid::v7()->toRfc4122()),
            $account->id(),
            $this->refreshTokenHasher->hash($rawRefreshToken),
            $familyId ?? RefreshSessionFamilyId::fromString(Uuid::v7()->toRfc4122()),
            $expiresAt ?? new \DateTimeImmutable('2026-06-03T12:00:00+00:00'),
            $createdAt,
        );

        if (null !== $revokedAt) {
            $session->revoke($revokedAt);
        }

        if (null !== $replacedByTokenId) {
            $session->replaceWith($replacedByTokenId, $createdAt->modify('+1 minute'));
        }

        $this->refreshSessions->save($session);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $session;
    }

    private function refreshCookie(): Cookie
    {
        $cookies = $this->client->getResponse()->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ('refresh_token' === $cookie->getName()) {
                return $cookie;
            }
        }

        self::fail('The response did not set a refresh_token cookie.');
    }

    private function setCookieHeader(): string
    {
        $header = $this->client->getResponse()->headers->get('Set-Cookie');
        self::assertIsString($header);

        return $header;
    }

    /** @return list<string> */
    private function storedRefreshTokenHashes(Account $account): array
    {
        $rows = $this->entityManager->getConnection()->fetchFirstColumn(
            'SELECT token_hash FROM refresh_sessions WHERE account_id = :accountId ORDER BY created_at ASC',
            ['accountId' => $account->id()->value()],
        );

        self::assertNotEmpty($rows);

        return array_map(static function (mixed $row): string {
            self::assertIsString($row);

            return $row;
        }, $rows);
    }

    private function revokedFamilySessionCount(RefreshSessionFamilyId $familyId): int
    {
        $count = $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM refresh_sessions WHERE family_id = :familyId AND revoked_at IS NOT NULL',
            ['familyId' => $familyId->value()],
        );

        self::assertIsInt($count);

        return $count;
    }

    private function assertInvalidRefreshTokenError(): void
    {
        $data = $this->responseData();

        self::assertSame('invalid_refresh_token', $data['error']['code']);
        self::assertSame('Invalid refresh token.', $data['error']['message']);
    }

    private function assertClearsRefreshCookie(): void
    {
        $cookie = $this->refreshCookie();

        self::assertContains($cookie->getValue(), [null, '']);
        self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/api/auth', $cookie->getPath());
    }
}
