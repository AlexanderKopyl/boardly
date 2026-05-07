<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenVerifierInterface;
use App\Boardly\IdentityAccess\Application\Security\AccessToken;
use App\Boardly\IdentityAccess\Application\Security\VerifiedAccessToken;
use App\Boardly\IdentityAccess\Infrastructure\Security\AccessTokenVerificationFailed;
use App\Boardly\IdentityAccess\Infrastructure\Security\JwtAccessTokenService;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class JwtAccessTokenServiceTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string TOKEN_ID = '018f3f7a-9e4c-7b2d-9c52-000000000101';
    private const string SIGNING_SECRET = 'test-access-token-signing-secret-with-32-bytes';
    private const string WRONG_SIGNING_SECRET = 'wrong-access-token-signing-secret-with-32-byte';

    private \DateTimeImmutable $issuedAt;

    protected function setUp(): void
    {
        $this->issuedAt = new \DateTimeImmutable('2026-05-04T08:00:00+00:00');
    }

    protected function tearDown(): void
    {
        JWT::$timestamp = null;
    }

    public function testIssuerCreatesTokenWithMinimalIdentityClaims(): void
    {
        $token = $this->service(now: $this->issuedAt)->issueForAccount(
            AccountId::fromString(self::ACCOUNT_ID),
            $this->issuedAt,
        );

        self::assertInstanceOf(AccessToken::class, $token);
        self::assertNotSame('', $token->token());
        self::assertEquals($this->issuedAt->modify('+900 seconds'), $token->expiresAt());
        self::assertSame(900, $token->expiresInSeconds());

        $claims = $this->decodePayloadWithoutVerification($token->token());
        self::assertSame(self::ACCOUNT_ID, $claims['sub']);
        self::assertSame($this->issuedAt->getTimestamp(), $claims['iat']);
        self::assertSame($this->issuedAt->modify('+900 seconds')->getTimestamp(), $claims['exp']);
        self::assertSame(self::TOKEN_ID, $claims['jti']);
        self::assertArrayNotHasKey('email', $claims);
        self::assertArrayNotHasKey('roles', $claims);
        self::assertArrayNotHasKey('permissions', $claims);
    }

    public function testVerifierAcceptsValidToken(): void
    {
        $service = $this->service(now: $this->issuedAt->modify('+60 seconds'));
        $token = $service->issueForAccount(AccountId::fromString(self::ACCOUNT_ID), $this->issuedAt);

        $verified = $service->verify($token->token());

        self::assertInstanceOf(VerifiedAccessToken::class, $verified);
        self::assertTrue(AccountId::fromString(self::ACCOUNT_ID)->equals($verified->accountId()));
        self::assertSame(self::TOKEN_ID, $verified->tokenId());
        self::assertEquals($this->issuedAt, $verified->issuedAt());
        self::assertEquals($this->issuedAt->modify('+900 seconds'), $verified->expiresAt());
    }

    public function testVerifierRejectsExpiredToken(): void
    {
        $issuer = $this->service(now: $this->issuedAt);
        $token = $issuer->issueForAccount(AccountId::fromString(self::ACCOUNT_ID), $this->issuedAt);

        $this->expectException(AccessTokenVerificationFailed::class);

        $this->service(now: $this->issuedAt->modify('+901 seconds'))->verify($token->token());
    }

    public function testVerifierRejectsMalformedToken(): void
    {
        $this->expectException(AccessTokenVerificationFailed::class);

        $this->service(now: $this->issuedAt)->verify('not-a-token');
    }

    public function testVerifierRejectsTokenSignedWithWrongSecret(): void
    {
        $token = $this->service(now: $this->issuedAt, signingSecret: self::WRONG_SIGNING_SECRET)
            ->issueForAccount(AccountId::fromString(self::ACCOUNT_ID), $this->issuedAt);

        $this->expectException(AccessTokenVerificationFailed::class);

        $this->service(now: $this->issuedAt->modify('+60 seconds'))->verify($token->token());
    }

    public function testApplicationPortsExposeNoVendorTokenClasses(): void
    {
        foreach ([AccessTokenIssuerInterface::class, AccessTokenVerifierInterface::class, AccessToken::class, VerifiedAccessToken::class] as $className) {
            $reflection = new ReflectionClass($className);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ([$method->getReturnType(), ...array_map(
                    static fn (\ReflectionParameter $parameter): ?\ReflectionType => $parameter->getType(),
                    $method->getParameters(),
                )] as $type) {
                    if (!$type instanceof \ReflectionNamedType) {
                        continue;
                    }

                    self::assertFalse(str_starts_with($type->getName(), 'Firebase\\JWT\\'));
                    self::assertFalse(str_starts_with($type->getName(), 'Lcobucci\\JWT\\'));
                }
            }
        }
    }

    private function service(
        \DateTimeImmutable $now,
        string $signingSecret = self::SIGNING_SECRET,
    ): JwtAccessTokenService {
        return new JwtAccessTokenService(
            $signingSecret,
            900,
            new FakeIdGenerator(self::TOKEN_ID),
            new FakeClock($now),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayloadWithoutVerification(string $token): array
    {
        $segments = explode('.', $token);
        self::assertCount(3, $segments);

        $payload = json_decode((string) base64_decode(strtr($segments[1], '-_', '+/')), true);
        self::assertIsArray($payload);

        return $payload;
    }
}

final readonly class FakeClock implements ClockInterface
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeIdGenerator implements IdGeneratorInterface
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function generate(): string
    {
        return $this->id;
    }
}
