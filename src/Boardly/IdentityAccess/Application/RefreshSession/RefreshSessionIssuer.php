<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;

final readonly class RefreshSessionIssuer
{
    private const string REFRESH_TOKEN_TTL = '+30 days';

    public function __construct(
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function issueForAccount(AccountId $accountId): RefreshSessionIssueResult
    {
        $now = $this->clock->now();
        $rawRefreshToken = $this->refreshTokenGenerator->generate();
        $refreshTokenHash = $this->refreshTokenHasher->hash($rawRefreshToken);
        $refreshTokenExpiresAt = $now->modify(self::REFRESH_TOKEN_TTL);

        $session = RefreshSession::create(
            RefreshSessionId::fromString($this->idGenerator->generate()),
            $accountId,
            $refreshTokenHash,
            RefreshSessionFamilyId::fromString($this->idGenerator->generate()),
            $refreshTokenExpiresAt,
            $now,
        );

        $this->refreshSessions->save($session);

        return new RefreshSessionIssueResult($rawRefreshToken, $refreshTokenExpiresAt);
    }
}
