<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenVerifierInterface;
use App\Boardly\IdentityAccess\Application\Security\AccessToken;
use App\Boardly\IdentityAccess\Application\Security\VerifiedAccessToken;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final readonly class JwtAccessTokenService implements AccessTokenIssuerInterface, AccessTokenVerifierInterface
{
    private const string ALGORITHM = 'HS256';

    public function __construct(
        #[\SensitiveParameter]
        private string $signingSecret,
        private int $ttlSeconds,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
    ) {
        if ('' === trim($this->signingSecret)) {
            throw new \InvalidArgumentException('Access token signing secret cannot be empty.');
        }

        if ($this->ttlSeconds <= 0) {
            throw new \InvalidArgumentException('Access token TTL must be positive.');
        }
    }

    public function issueForAccount(AccountId $accountId, \DateTimeImmutable $issuedAt, ?int $ttlSeconds = null): AccessToken
    {
        $effectiveTtlSeconds = $ttlSeconds ?? $this->ttlSeconds;
        if ($effectiveTtlSeconds <= 0) {
            throw new \InvalidArgumentException('Access token TTL must be positive.');
        }

        $expiresAt = $issuedAt->modify(sprintf('+%d seconds', $effectiveTtlSeconds));

        $payload = [
            'sub' => $accountId->value(),
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'jti' => $this->idGenerator->generate(),
        ];

        return new AccessToken(
            JWT::encode($payload, $this->signingSecret, self::ALGORITHM),
            $expiresAt,
            $effectiveTtlSeconds,
        );
    }

    public function verify(string $token): VerifiedAccessToken
    {
        if ('' === trim($token)) {
            throw AccessTokenVerificationFailed::invalid();
        }

        $previousTimestamp = JWT::$timestamp;
        JWT::$timestamp = $this->clock->now()->getTimestamp();

        try {
            $payload = JWT::decode($token, new Key($this->signingSecret, self::ALGORITHM));
        } catch (Throwable $exception) {
            throw AccessTokenVerificationFailed::invalid();
        } finally {
            JWT::$timestamp = $previousTimestamp;
        }

        if (
            !isset($payload->sub, $payload->iat, $payload->exp, $payload->jti)
            || !is_string($payload->sub)
            || !is_numeric($payload->iat)
            || !is_numeric($payload->exp)
            || !is_string($payload->jti)
        ) {
            throw AccessTokenVerificationFailed::invalid();
        }

        try {
            return new VerifiedAccessToken(
                AccountId::fromString($payload->sub),
                $payload->jti,
                $this->dateTimeFromTimestamp((int) $payload->iat),
                $this->dateTimeFromTimestamp((int) $payload->exp),
            );
        } catch (Throwable) {
            throw AccessTokenVerificationFailed::invalid();
        }
    }

    private function dateTimeFromTimestamp(int $timestamp): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@'.$timestamp))->setTimezone(new \DateTimeZone('UTC'));
    }
}
