<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenVerifierInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Security\VerifiedAccessToken;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser;
use App\Boardly\IdentityAccess\Infrastructure\Security\AccessTokenVerificationFailed;
use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticationFailureResponseFactory;
use App\Boardly\IdentityAccess\Infrastructure\Security\BearerTokenAuthenticator;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class BearerTokenAuthenticatorTest extends TestCase
{
    public function testSupportsRequestWithBearerToken(): void
    {
        $authenticator = $this->authenticator();

        self::assertTrue($authenticator->supports($this->requestWithBearerToken('valid-token')));
    }

    public function testDoesNotSupportRequestWithoutBearerToken(): void
    {
        $authenticator = $this->authenticator();

        self::assertFalse($authenticator->supports(Request::create('/api/projects', 'GET')));
    }

    public function testSupportsPublicAuthEndpointWhenBearerTokenIsPresent(): void
    {
        $authenticator = $this->authenticator();
        $request = Request::create('/api/auth/login', 'POST');
        $request->headers->set('Authorization', 'Bearer valid-token');

        self::assertTrue($authenticator->supports($request));
    }

    public function testMissingAuthorizationHeaderFailsWithGeneric401(): void
    {
        $authenticator = $this->authenticator();

        $this->assertAuthenticationFailureResponse($authenticator, Request::create('/api/projects', 'GET'));
    }

    public function testNonBearerAuthorizationHeaderFailsWithGeneric401(): void
    {
        $authenticator = $this->authenticator();
        $request = Request::create('/api/projects', 'GET');
        $request->headers->set('Authorization', 'Basic abc123');

        $this->assertAuthenticationFailureResponse($authenticator, $request);
    }

    public function testEmptyBearerTokenFailsWithGeneric401(): void
    {
        $authenticator = $this->authenticator();
        $request = Request::create('/api/projects', 'GET');
        $request->headers->set('Authorization', 'Bearer    ');

        $this->assertAuthenticationFailureResponse($authenticator, $request);
    }

    #[DataProvider('invalidTokenProvider')]
    public function testMalformedInvalidOrExpiredTokenFailsWithGeneric401(string $token): void
    {
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier(failure: AccessTokenVerificationFailed::invalid()),
        );
        $request = $this->requestWithBearerToken($token);

        $this->assertAuthenticationFailureResponse($authenticator, $request);
    }

    public function testUnexpectedVerifierFailureIsNotConvertedToAuthenticationFailure(): void
    {
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier(failure: new \LogicException('misconfigured verifier')),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('misconfigured verifier');

        $authenticator->authenticate($this->requestWithBearerToken('valid-token'));
    }

    public function testTokenForMissingAccountFailsWithGeneric401(): void
    {
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier($this->verifiedToken($this->accountId())),
        );

        $this->assertAuthenticationFailureResponse($authenticator, $this->requestWithBearerToken('valid-token'));
    }

    #[DataProvider('nonActiveStatusProvider')]
    public function testTokenForNonActiveAccountFailsWithGeneric401(string $status): void
    {
        $accountId = $this->accountId();
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier($this->verifiedToken($accountId)),
            accounts: new FakeAccountRepository($this->account($accountId, $status)),
        );

        $this->assertAuthenticationFailureResponse($authenticator, $this->requestWithBearerToken('valid-token'));
    }

    public function testValidTokenForActiveAccountAuthenticates(): void
    {
        $accountId = $this->accountId();
        $account = $this->account($accountId, 'active');
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier($this->verifiedToken($accountId)),
            accounts: new FakeAccountRepository($account),
        );

        $passport = $authenticator->authenticate($this->requestWithBearerToken('valid-token'));
        $user = $passport->getUser();

        self::assertInstanceOf(AuthenticatedAccountUser::class, $user);
        self::assertSame($accountId->value(), $user->getUserIdentifier());
    }

    public function testAuthenticatedUserExposesAccountFieldsAndRoleUser(): void
    {
        $account = $this->account($this->accountId(), 'active', 'security-user@example.com', 'Security User');

        $user = AuthenticatedAccountUser::fromAccount($account);

        self::assertSame($account->id(), $user->accountId());
        self::assertSame('security-user@example.com', $user->email());
        self::assertSame('Security User', $user->name());
        self::assertSame('active', $user->status());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testAuthenticatorDoesNotExposeFailureReason(): void
    {
        $authenticator = $this->authenticator(
            verifier: new FakeAccessTokenVerifier(failure: AccessTokenVerificationFailed::invalid()),
        );

        $response = $this->failureResponseFor($authenticator, $this->requestWithBearerToken('expired-token'));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
        self::assertStringNotContainsString('expired', (string) $response->getContent());
        self::assertStringNotContainsString('abc', (string) $response->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTokenProvider(): iterable
    {
        yield 'malformed' => ['not-a-jwt'];
        yield 'invalid' => ['invalid-token'];
        yield 'expired' => ['expired-token'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonActiveStatusProvider(): iterable
    {
        yield 'pending approval' => ['pending_approval'];
        yield 'rejected' => ['rejected'];
        yield 'disabled' => ['disabled'];
    }

    private function authenticator(
        ?AccessTokenVerifierInterface $verifier = null,
        ?AccountRepositoryInterface $accounts = null,
    ): BearerTokenAuthenticator {
        return new BearerTokenAuthenticator(
            $verifier ?? new FakeAccessTokenVerifier($this->verifiedToken($this->accountId())),
            $accounts ?? new FakeAccountRepository(),
            new AuthenticationFailureResponseFactory(),
        );
    }

    private function assertAuthenticationFailureResponse(BearerTokenAuthenticator $authenticator, Request $request): void
    {
        $response = $this->failureResponseFor($authenticator, $request);

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function failureResponseFor(BearerTokenAuthenticator $authenticator, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $authenticator->authenticate($request);
            self::fail('Authentication was expected to fail.');
        } catch (AuthenticationException $exception) {
            return $authenticator->onAuthenticationFailure($request, $exception);
        }
    }

    private function requestWithBearerToken(string $token): Request
    {
        $request = Request::create('/api/projects', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        return $request;
    }

    private function verifiedToken(AccountId $accountId): VerifiedAccessToken
    {
        return new VerifiedAccessToken(
            $accountId,
            'token-id',
            new \DateTimeImmutable('2026-05-05T10:00:00+00:00'),
            new \DateTimeImmutable('2026-05-05T10:15:00+00:00'),
        );
    }

    private function accountId(): AccountId
    {
        return AccountId::fromString('0196976b-0000-7000-8000-000000000001');
    }

    private function account(
        AccountId $accountId,
        string $status,
        string $email = 'user@example.com',
        string $name = 'User Name',
    ): Account {
        $createdAt = new \DateTimeImmutable('2026-05-05T10:00:00+00:00');
        $account = Account::register(
            $accountId,
            Email::fromString($email),
            PasswordHash::fromString(password_hash('Password123!', PASSWORD_BCRYPT)),
            AccountName::fromString($name),
            $createdAt,
        )->account();

        match ($status) {
            'active' => $account->approve($createdAt->modify('+1 minute')),
            'rejected' => $account->reject($createdAt->modify('+1 minute')),
            'disabled' => $this->disable($account, $createdAt),
            'pending_approval' => null,
            default => throw new \InvalidArgumentException(sprintf('Unsupported status "%s".', $status)),
        };

        return $account;
    }

    private function disable(Account $account, \DateTimeImmutable $createdAt): void
    {
        $account->approve($createdAt->modify('+1 minute'));
        $account->disable($createdAt->modify('+2 minutes'));
    }
}

final class FakeAccessTokenVerifier implements AccessTokenVerifierInterface
{
    public function __construct(
        private ?VerifiedAccessToken $verifiedToken = null,
        private ?\Throwable $failure = null,
    ) {
    }

    public function verify(string $token): VerifiedAccessToken
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }

        Assert::assertNotSame('', trim($token));
        Assert::assertNotNull($this->verifiedToken);

        return $this->verifiedToken;
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    public function __construct(private ?Account $account = null)
    {
    }

    public function save(Account $account): void
    {
        $this->account = $account;
    }

    public function get(AccountId $id): Account
    {
        return $this->account ?? throw AccountNotFound::withId($id);
    }

    public function find(AccountId $id): ?Account
    {
        if (null === $this->account || $this->account->id()->value() !== $id->value()) {
            return null;
        }

        return $this->account;
    }

    public function findByEmail(Email $email): ?Account
    {
        if (null === $this->account || !$this->account->email()->equals($email)) {
            return null;
        }

        return $this->account;
    }

    public function existsByEmail(Email $email): bool
    {
        return null !== $this->findByEmail($email);
    }

    public function countActiveSystemAdmins(): int
    {
        return 0;
    }
}
