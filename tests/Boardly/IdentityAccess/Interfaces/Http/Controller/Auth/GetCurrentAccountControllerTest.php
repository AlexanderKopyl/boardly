<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

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

final class GetCurrentAccountControllerTest extends WebTestCase
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

    public function testMissingBearerTokenReturnsUnauthorizedJson(): void
    {
        $this->requestCurrentAccount();

        $this->assertUnauthorizedResponse();
    }

    public function testInvalidBearerTokenReturnsUnauthorizedJson(): void
    {
        $this->requestCurrentAccount('not-a-jwt');

        $this->assertUnauthorizedResponse();
    }

    public function testExpiredBearerTokenReturnsUnauthorizedJson(): void
    {
        $accountId = AccountId::fromString(Uuid::v7()->toRfc4122());
        $token = $this->accessTokenIssuer
            ->issueForAccount($accountId, new \DateTimeImmutable('2020-01-01T00:00:00+00:00'), 1)
            ->token();

        $this->requestCurrentAccount($token);

        $this->assertUnauthorizedResponse();
    }

    public function testTokenForMissingAccountReturnsUnauthorizedJson(): void
    {
        $accountId = AccountId::fromString(Uuid::v7()->toRfc4122());

        $this->requestCurrentAccount($this->validTokenFor($accountId));

        $this->assertUnauthorizedResponse();
    }

    public function testTokenForNonActiveAccountReturnsUnauthorizedJson(): void
    {
        $account = $this->persistAccount('disabled-current@example.com', 'Disabled Current', 'disabled');

        $this->requestCurrentAccount($this->validTokenFor($account->id()));

        $this->assertUnauthorizedResponse();
    }

    public function testValidActiveAccountReturnsSafeIdentityOnly(): void
    {
        $account = $this->persistAccount('active-current@example.com', 'Active Current', 'active');

        $this->requestCurrentAccount($this->validTokenFor($account->id()));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(
            [
                'id' => $account->id()->value(),
                'email' => 'active-current@example.com',
                'name' => 'Active Current',
                'status' => 'active',
            ],
            $this->responseData(),
        );

        $body = (string) $this->client->getResponse()->getContent();

        self::assertStringNotContainsString('password', strtolower($body));
        self::assertStringNotContainsString('refresh_token', strtolower($body));
        self::assertStringNotContainsString('access_token', strtolower($body));
        self::assertStringNotContainsString('roles', strtolower($body));
        self::assertStringNotContainsString('systemadmin', strtolower($body));
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

    private function requestCurrentAccount(?string $accessToken = null): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];

        if (null !== $accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken;
        }

        $this->client->request('GET', '/api/auth/me', [], [], $server);
    }

    private function validTokenFor(AccountId $accountId): string
    {
        return $this->accessTokenIssuer
            ->issueForAccount($accountId, (new \DateTimeImmutable('now'))->modify('-1 minute'))
            ->token();
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

    private function assertUnauthorizedResponse(): void
    {
        self::assertResponseStatusCodeSame(401);
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            $this->responseData(),
        );

        $body = strtolower((string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('invalid', $body);
        self::assertStringNotContainsString('malformed', $body);
        self::assertStringNotContainsString('disabled', $body);
        self::assertStringNotContainsString('missing', $body);
        self::assertStringNotContainsString('token', $body);
        self::assertStringNotContainsString('claims', $body);
    }

    private function persistAccount(string $email, string $name, string $status): Account
    {
        $createdAt = new \DateTimeImmutable('2026-05-14T09:30:00+00:00');
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
}
