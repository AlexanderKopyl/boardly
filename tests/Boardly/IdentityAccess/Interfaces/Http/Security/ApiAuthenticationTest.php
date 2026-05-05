<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Security;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class ApiAuthenticationTest extends WebTestCase
{
    private const string PLAIN_PASSWORD = 'Password123!';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AccountRepositoryInterface $accounts;
    private PasswordHasherInterface $passwordHasher;
    private AccessTokenIssuerInterface $accessTokenIssuer;

    protected function setUp(): void
    {
        $this->setRequiredTestSecrets();

        $this->client = self::createClient();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->accounts = $container->get(AccountRepositoryInterface::class);
        $this->passwordHasher = $container->get(PasswordHasherInterface::class);
        $this->accessTokenIssuer = $container->get(AccessTokenIssuerInterface::class);

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

    public function testRegisterRemainsReachableWithoutAuthorization(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'email' => 'register-public@example.com',
                'plainPassword' => self::PLAIN_PASSWORD,
                'name' => 'Register Public',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        self::assertSame('pending_approval', $this->responseData()['status']);
    }

    public function testLoginRemainsReachableWithoutAuthorization(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode([
                'email' => 'missing-login@example.com',
                'plainPassword' => self::PLAIN_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
        self::assertSame('invalid_credentials', $this->responseData()['error']['code']);
    }

    public function testRefreshRemainsReachableWithoutAuthorizationAndUsesRefreshError(): void
    {
        $this->client->request('POST', '/api/auth/refresh', [], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('invalid_refresh_token', $this->responseData()['error']['code']);
    }

    public function testLogoutRemainsReachableWithoutAuthorization(): void
    {
        $this->client->request('POST', '/api/auth/logout', [], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', (string) $this->client->getResponse()->getContent());
    }

    public function testProtectedApiRouteWithoutAuthorizationReturnsUnauthorizedJson(): void
    {
        $this->getProtectedApiRoute();

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
    }

    public function testProtectedApiRouteWithMalformedTokenReturnsUnauthorizedJson(): void
    {
        $this->getProtectedApiRoute('not-a-jwt');

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
    }

    public function testProtectedApiRouteWithExpiredTokenReturnsUnauthorizedJson(): void
    {
        $accountId = AccountId::fromString(Uuid::v7()->toRfc4122());
        $token = $this->accessTokenIssuer
            ->issueForAccount($accountId, new \DateTimeImmutable('2020-01-01T00:00:00+00:00'), 1)
            ->token();

        $this->getProtectedApiRoute($token);

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
    }

    public function testProtectedApiRouteWithTokenForMissingAccountReturnsUnauthorizedJson(): void
    {
        $accountId = AccountId::fromString(Uuid::v7()->toRfc4122());

        $this->getProtectedApiRoute($this->validTokenFor($accountId));

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
    }

    public function testProtectedApiRouteWithTokenForDisabledAccountReturnsUnauthorizedJson(): void
    {
        $account = $this->persistAccount('disabled-security@example.com', 'Disabled Security', 'disabled');

        $this->getProtectedApiRoute($this->validTokenFor($account->id()));

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
    }

    public function testProtectedApiRouteWithValidTokenForActiveAccountReturnsSuccess(): void
    {
        $account = $this->persistAccount('active-security@example.com', 'Active Security', 'active');

        $this->getProtectedApiRoute($this->validTokenFor($account->id()));

        self::assertResponseStatusCodeSame(200);
        self::assertSame([
            'ok' => true,
            'account' => [
                'id' => $account->id()->value(),
                'email' => 'active-security@example.com',
                'name' => 'Active Security',
                'status' => 'active',
                'roles' => ['ROLE_USER'],
            ],
        ], $this->responseData());
    }

    public function testUnauthorizedResponseNeverExposesTokenClaimsOrFailureReason(): void
    {
        $accountId = AccountId::fromString(Uuid::v7()->toRfc4122());
        $token = $this->accessTokenIssuer
            ->issueForAccount($accountId, new \DateTimeImmutable('2020-01-01T00:00:00+00:00'), 1)
            ->token();

        $this->getProtectedApiRoute($token);

        $body = (string) $this->client->getResponse()->getContent();

        $this->assertUnauthorizedResponseDoesNotExposeDetails();
        self::assertStringNotContainsString($accountId->value(), $body);
        self::assertStringNotContainsString('expired', strtolower($body));
        self::assertStringNotContainsString('sub', $body);
        self::assertStringNotContainsString('iat', $body);
        self::assertStringNotContainsString('exp', $body);
        self::assertStringNotContainsString('jti', $body);
    }

    private function setRequiredTestSecrets(): void
    {
        $_ENV['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_SERVER['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_ENV['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
        $_SERVER['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
    }

    private function getProtectedApiRoute(?string $accessToken = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];

        if (null !== $accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken;
        }

        $this->client->request('GET', '/api/test/protected', [], [], $server);
    }

    private function validTokenFor(AccountId $accountId): string
    {
        return $this->accessTokenIssuer
            ->issueForAccount($accountId, (new \DateTimeImmutable('now'))->modify('-1 minute'))
            ->token();
    }

    private function assertUnauthorizedResponseDoesNotExposeDetails(): void
    {
        self::assertResponseStatusCodeSame(401);
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            $this->responseData(),
        );

        $body = (string) $this->client->getResponse()->getContent();

        self::assertStringNotContainsString('invalid', strtolower($body));
        self::assertStringNotContainsString('malformed', strtolower($body));
        self::assertStringNotContainsString('disabled', strtolower($body));
        self::assertStringNotContainsString('missing', strtolower($body));
        self::assertStringNotContainsString('token', strtolower($body));
        self::assertStringNotContainsString('claims', strtolower($body));
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
        $createdAt = new \DateTimeImmutable('2026-05-05T10:00:00+00:00');
        $account = Account::register(
            AccountId::fromString(Uuid::v7()->toRfc4122()),
            Email::fromString($email),
            PasswordHash::fromString($this->passwordHasher->hash(self::PLAIN_PASSWORD)),
            AccountName::fromString($name),
            $createdAt,
        )->account();

        match ($status) {
            'active' => $account->approve($createdAt->modify('+1 minute')),
            'disabled' => $this->disable($account, $createdAt),
            default => throw new \InvalidArgumentException(sprintf('Unsupported status "%s".', $status)),
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
}
