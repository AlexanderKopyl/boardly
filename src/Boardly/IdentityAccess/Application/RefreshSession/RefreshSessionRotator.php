<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

use App\Boardly\IdentityAccess\Application\Exception\InvalidRefreshToken;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;

final readonly class RefreshSessionRotator
{
    private const string REFRESH_TOKEN_TTL = '+30 days';

    public function __construct(
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private AccountRepositoryInterface $accounts,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    /**
     * @param callable(AccountId, \DateTimeImmutable): void $afterValidation
     */
    public function rotate(string $rawRefreshToken, callable $afterValidation): RefreshSessionRotationResult
    {
        $now = $this->clock->now();
        $tokenHash = $this->refreshTokenHasher->hash($rawRefreshToken);
        $currentSession = $this->refreshSessions->findByTokenHashForUpdate($tokenHash);

        if (null === $currentSession) {
            throw InvalidRefreshToken::create();
        }

        if ($currentSession->wasReplaced()) {
            $this->refreshSessions->revokeFamily($currentSession->familyId(), $now);

            throw InvalidRefreshToken::create();
        }

        if ($currentSession->isExpired($now) || $currentSession->isRevoked()) {
            throw InvalidRefreshToken::create();
        }

        $account = $this->accounts->find($currentSession->accountId());

        if (null === $account || !$account->status()->isActive()) {
            throw InvalidRefreshToken::create();
        }

        $newRawRefreshToken = $this->refreshTokenGenerator->generate();
        $newTokenHash = $this->refreshTokenHasher->hash($newRawRefreshToken);
        $newSessionId = RefreshSessionId::fromString($this->idGenerator->generate());
        $refreshTokenExpiresAt = $now->modify(self::REFRESH_TOKEN_TTL);

        $afterValidation($account->id(), $now);

        $currentSession->markUsed($now);
        $currentSession->replaceWith($newSessionId, $now);

        $newSession = RefreshSession::create(
            $newSessionId,
            $account->id(),
            $newTokenHash,
            $currentSession->familyId(),
            $refreshTokenExpiresAt,
            $now,
        );

        $this->refreshSessions->save($currentSession);
        $this->refreshSessions->save($newSession);

        return new RefreshSessionRotationResult($account->id(), $newRawRefreshToken, $refreshTokenExpiresAt);
    }
}
