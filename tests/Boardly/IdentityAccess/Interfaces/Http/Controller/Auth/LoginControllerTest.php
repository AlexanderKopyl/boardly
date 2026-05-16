<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Bus\CommandBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Uid\Uuid;

final class LoginControllerTest extends WebTestCase
{
    private const string PLAIN_PASSWORD = 'Password123!';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AccountRepositoryInterface $accounts;
    private PasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->setRequiredTestSecrets();

        $this->client = self::createClient();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->accounts = $container->get(AccountRepositoryInterface::class);
        $this->passwordHasher = $container->get(PasswordHasherInterface::class);

        self::assertTrue(
            $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['accounts', 'projects.projects', 'refresh_sessions']),
            'The accounts, projects.projects and refresh_sessions tables must exist. Run doctrine:migrations:migrate --env=test before this test.',
        );

        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM projects.projects');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM refresh_sessions');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM accounts');
    }

    private function setRequiredTestSecrets(): void
    {
        $_ENV['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_SERVER['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_ENV['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
        $_SERVER['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testSuccessfulLoginReturns200(): void
    {
        $this->persistAccount('active@example.com', 'Active User', 'active');

        $this->postLogin($this->validPayload('active@example.com'));

        self::assertResponseStatusCodeSame(200);
    }

    public function testSuccessfulLoginReturnsAccessTokenResponse(): void
    {
        $account = $this->persistAccount('account-response@example.com', 'Account User', 'active');

        $this->postLogin($this->validPayload('account-response@example.com'));

        $data = $this->responseData();

        self::assertSame(['accessToken', 'tokenType', 'expiresIn', 'account'], array_keys($data));
        self::assertArrayHasKey('accessToken', $data);
        self::assertIsString($data['accessToken']);
        self::assertNotEmpty($data['accessToken']);
        self::assertSame('Bearer', $data['tokenType']);
        self::assertSame(900, $data['expiresIn']);
        self::assertSame([
            'id' => $account->id()->value(),
            'email' => 'account-response@example.com',
            'name' => 'Account User',
            'status' => 'active',
        ], $data['account']);
    }

    public function testSuccessfulResponseDoesNotExposeSensitiveFields(): void
    {
        $this->persistAccount('sensitive@example.com', 'Sensitive User', 'active');

        $this->postLogin($this->validPayload('sensitive@example.com'));

        $json = (string) $this->client->getResponse()->getContent();
        $data = $this->responseData();

        self::assertArrayNotHasKey('refreshToken', $data);
        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('passwordHash', $data);
        self::assertArrayNotHasKey('refreshTokenHash', $data);
        self::assertArrayNotHasKey('password', $data['account']);
        self::assertArrayNotHasKey('passwordHash', $data['account']);
        self::assertStringNotContainsString('refreshToken', $json);
        self::assertStringNotContainsString('passwordHash', $json);
    }

    public function testSuccessfulLoginSetsRefreshCookie(): void
    {
        $account = $this->persistAccount('cookie@example.com', 'Cookie User', 'active');

        $this->postLogin($this->validPayload('cookie@example.com'));

        $cookie = $this->refreshCookie();
        $tokenHash = $this->storedRefreshTokenHash($account);

        self::assertSame('refresh_token', $cookie->getName());
        self::assertNotEmpty($cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('/api/auth', $cookie->getPath());
        self::assertSame(Cookie::SAMESITE_NONE, $cookie->getSameSite());
        self::assertGreaterThan(time(), $cookie->getExpiresTime());
        $setCookieHeader = strtolower($this->setCookieHeader());
        self::assertStringContainsString('refresh_token=', $setCookieHeader);
        self::assertStringContainsString('httponly', $setCookieHeader);
        self::assertStringContainsString('path=/api/auth', $setCookieHeader);
        self::assertStringContainsString('samesite=none', $setCookieHeader);
        self::assertNotSame($cookie->getValue(), $tokenHash);
        self::assertStringNotContainsString((string) $cookie->getValue(), (string) $this->client->getResponse()->getContent());
    }

    public function testUnknownEmailReturnsInvalidCredentials401(): void
    {
        $this->postLogin($this->validPayload('missing@example.com'));

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidCredentialsError();
    }

    public function testWrongPasswordReturnsSameInvalidCredentials401(): void
    {
        $this->persistAccount('wrong-password@example.com', 'Wrong Password User', 'active');

        $this->postLogin($this->validPayload('wrong-password@example.com', 'wrong-password'));

        self::assertResponseStatusCodeSame(401);
        $this->assertInvalidCredentialsError();
    }

    public function testPendingApprovalAccountReturnsAccountNotActive403(): void
    {
        $this->persistAccount('pending@example.com', 'Pending User', 'pending_approval');

        $this->postLogin($this->validPayload('pending@example.com'));

        self::assertResponseStatusCodeSame(403);
        $this->assertAccountNotActiveError();
    }

    public function testRejectedAccountReturnsAccountNotActive403(): void
    {
        $this->persistAccount('rejected@example.com', 'Rejected User', 'rejected');

        $this->postLogin($this->validPayload('rejected@example.com'));

        self::assertResponseStatusCodeSame(403);
        $this->assertAccountNotActiveError();
    }

    public function testDisabledAccountReturnsAccountNotActive403(): void
    {
        $this->persistAccount('disabled@example.com', 'Disabled User', 'disabled');

        $this->postLogin($this->validPayload('disabled@example.com'));

        self::assertResponseStatusCodeSame(403);
        $this->assertAccountNotActiveError();
    }

    public function testMalformedJsonReturns400(): void
    {
        $this->postLogin('{not valid json}');

        self::assertResponseStatusCodeSame(400);

        $data = $this->responseData();

        self::assertSame('invalid_request', $data['error']['code']);
        self::assertSame('Invalid request body.', $data['error']['message']);
    }

    public function testEmptyRequestReturns422(): void
    {
        $this->postLogin([]);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testBlankEmailReturns422(): void
    {
        $this->postLogin($this->validPayload(''));

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testBlankPlainPasswordReturns422(): void
    {
        $this->postLogin($this->validPayload('blank-password@example.com', ''));

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testControllerUsesCommandBusInterface(): void
    {
        $constructor = new \ReflectionMethod(
            \App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\LoginController::class,
            '__construct',
        );
        $parameterType = $constructor->getParameters()[0]->getType();

        self::assertInstanceOf(\ReflectionNamedType::class, $parameterType);
        self::assertSame(CommandBusInterface::class, $parameterType->getName());
    }

    /** @param array<string, mixed> $payload */
    private function postLogin(array|string $payload): void
    {
        $content = \is_array($payload)
            ? json_encode($payload, JSON_THROW_ON_ERROR)
            : $payload;

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'Boardly HTTP test',
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTPS' => 'on',
            ],
            $content,
        );
    }

    /** @return array<string, string> */
    private function validPayload(string $email, string $plainPassword = self::PLAIN_PASSWORD): array
    {
        return [
            'email' => $email,
            'plainPassword' => $plainPassword,
        ];
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

    private function storedRefreshTokenHash(Account $account): string
    {
        $tokenHash = $this->entityManager->getConnection()->fetchOne(
            'SELECT token_hash FROM refresh_sessions WHERE account_id = :accountId',
            ['accountId' => $account->id()->value()],
        );

        self::assertIsString($tokenHash);
        self::assertNotEmpty($tokenHash);

        return $tokenHash;
    }

    private function assertInvalidCredentialsError(): void
    {
        $data = $this->responseData();

        self::assertSame('invalid_credentials', $data['error']['code']);
        self::assertSame('Invalid credentials.', $data['error']['message']);
    }

    private function assertAccountNotActiveError(): void
    {
        $data = $this->responseData();

        self::assertSame('account_not_active', $data['error']['code']);
        self::assertSame('Account is not active.', $data['error']['message']);
    }

    private function assertValidationFailedError(): void
    {
        $data = $this->responseData();

        self::assertSame('validation_failed', $data['error']['code']);
        self::assertSame('The request payload is invalid.', $data['error']['message']);
        self::assertArrayHasKey('violations', $data['error']);
        self::assertIsArray($data['error']['violations']);
        self::assertNotEmpty($data['error']['violations']);
    }
}
