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
use App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\LogoutController;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Bus\CommandBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class LogoutControllerTest extends WebTestCase
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

    public function testPostLogoutWithoutCookieReturns204(): void
    {
        $this->postLogout();

        self::assertResponseStatusCodeSame(204);
    }

    public function testResponseClearsRefreshTokenCookie(): void
    {
        $this->postLogout();

        $cookie = $this->refreshCookie();

        self::assertSame('refresh_token', $cookie->getName());
        self::assertContains($cookie->getValue(), [null, '']);
        self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
    }

    public function testClearCookieUsesApiAuthPath(): void
    {
        $this->postLogout();

        self::assertSame('/api/auth', $this->refreshCookie()->getPath());
        self::assertStringContainsString('path=/api/auth', strtolower($this->setCookieHeader()));
    }

    public function testClearCookieUsesHttpOnlySecureAndSameSiteLax(): void
    {
        $this->postLogout();

        $cookie = $this->refreshCookie();
        $setCookieHeader = strtolower($this->setCookieHeader());

        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertStringContainsString('httponly', $setCookieHeader);
        self::assertStringContainsString('secure', $setCookieHeader);
        self::assertStringContainsString('samesite=lax', $setCookieHeader);
    }

    public function testPostLogoutWithValidCookieReturns204(): void
    {
        $account = $this->persistAccount('logout-valid@example.com', 'Logout Valid');
        $this->persistRefreshSession($account, 'valid-refresh-token');

        $this->postLogout('valid-refresh-token');

        self::assertResponseStatusCodeSame(204);
    }

    public function testValidSessionIsRevoked(): void
    {
        $account = $this->persistAccount('logout-revoke@example.com', 'Logout Revoke');
        $session = $this->persistRefreshSession($account, 'valid-refresh-token');

        $this->postLogout('valid-refresh-token');

        self::assertResponseStatusCodeSame(204);
        self::assertSame(1, $this->revokedSessionCount($session->id()));
    }

    public function testPostLogoutWithUnknownCookieStillReturns204(): void
    {
        $this->postLogout('unknown-refresh-token');

        self::assertResponseStatusCodeSame(204);
        $this->assertResponseBodyIsEmpty();
        $this->assertClearsRefreshCookie();
    }

    public function testResponseBodyIsEmpty(): void
    {
        $this->postLogout();

        self::assertResponseStatusCodeSame(204);
        $this->assertResponseBodyIsEmpty();
    }

    public function testResponseDoesNotExposeTokenPasswordOrSessionData(): void
    {
        $account = $this->persistAccount('logout-sensitive@example.com', 'Logout Sensitive');
        $this->persistRefreshSession($account, 'sensitive-refresh-token');

        $this->postLogout('sensitive-refresh-token');

        $body = (string) $this->client->getResponse()->getContent();

        self::assertSame('', $body);
        self::assertStringNotContainsString('sensitive-refresh-token', $body);
        self::assertStringNotContainsString('refreshToken', $body);
        self::assertStringNotContainsString('refresh_token', $body);
        self::assertStringNotContainsString('password', $body);
        self::assertStringNotContainsString('passwordHash', $body);
        self::assertStringNotContainsString('session', $body);
    }

    public function testControllerUsesCommandBusInterfacePathNotHandlerDirectly(): void
    {
        $constructor = new \ReflectionMethod(LogoutController::class, '__construct');
        $parameters = $constructor->getParameters();

        self::assertCount(1, $parameters);

        $parameterType = $parameters[0]->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $parameterType);
        self::assertSame(CommandBusInterface::class, $parameterType->getName());
        self::assertStringNotContainsString('LogoutHandler', (string) $parameterType);
    }

    private function setRequiredTestSecrets(): void
    {
        $_ENV['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_SERVER['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_ENV['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
        $_SERVER['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
    }

    private function postLogout(?string $rawRefreshToken = null): void
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
            '/api/auth/logout',
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

    private function persistAccount(string $email, string $name): Account
    {
        $createdAt = new \DateTimeImmutable('2026-05-04T12:00:00+00:00');
        $account = Account::register(
            AccountId::fromString(Uuid::v7()->toRfc4122()),
            Email::fromString($email),
            PasswordHash::fromString($this->passwordHasher->hash(self::PLAIN_PASSWORD)),
            AccountName::fromString($name),
            $createdAt,
        )->account();
        $account->approve($createdAt->modify('+1 minute'));

        $this->accounts->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $account;
    }

    private function persistRefreshSession(Account $account, string $rawRefreshToken): RefreshSession
    {
        $session = RefreshSession::create(
            RefreshSessionId::fromString(Uuid::v7()->toRfc4122()),
            $account->id(),
            $this->refreshTokenHasher->hash($rawRefreshToken),
            RefreshSessionFamilyId::fromString(Uuid::v7()->toRfc4122()),
            new \DateTimeImmutable('2026-06-03T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
        );

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

        self::fail('The response did not clear a refresh_token cookie.');
    }

    private function setCookieHeader(): string
    {
        $header = $this->client->getResponse()->headers->get('Set-Cookie');
        self::assertIsString($header);

        return $header;
    }

    private function revokedSessionCount(RefreshSessionId $sessionId): int
    {
        $count = $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM refresh_sessions WHERE id = :id AND revoked_at IS NOT NULL',
            ['id' => $sessionId->value()],
        );

        self::assertIsInt($count);

        return $count;
    }

    private function assertResponseBodyIsEmpty(): void
    {
        self::assertSame('', (string) $this->client->getResponse()->getContent());
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
