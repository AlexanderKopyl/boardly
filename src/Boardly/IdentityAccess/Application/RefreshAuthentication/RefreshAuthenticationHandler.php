<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshAuthentication;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class RefreshAuthenticationHandler
{
    private const string REFRESH_TOKEN_TTL = '+30 days';

    public function __construct(
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private AccountRepositoryInterface $accounts,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private TransactionalInterface $transactional,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RefreshAuthenticationCommand $command): RefreshAuthenticationResult
    {
        $rawRefreshToken = trim($command->rawRefreshToken());

        if ('' === $rawRefreshToken) {
            throw InvalidRefreshToken::create();
        }

        $now = $this->clock->now();
        $tokenHash = $this->refreshTokenHasher->hash($rawRefreshToken);
        $currentSession = $this->refreshSessions->findByTokenHash($tokenHash);

        if (null === $currentSession) {
            throw InvalidRefreshToken::create();
        }

        if ($currentSession->wasReplaced()) {
            $this->transactional->transactional(function () use ($currentSession, $now): void {
                $this->refreshSessions->revokeFamily($currentSession->familyId(), $now);
            });

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

        $this->transactional->transactional(function () use (
            $currentSession,
            $newSessionId,
            $account,
            $newTokenHash,
            $refreshTokenExpiresAt,
            $now,
        ): void {
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
        });

        $accessToken = $this->accessTokenIssuer->issueForAccount($account->id(), $now);

        return new RefreshAuthenticationResult(
            $accessToken->token(),
            $accessToken->expiresAt(),
            $accessToken->expiresInSeconds(),
            $newRawRefreshToken,
            $refreshTokenExpiresAt,
        );
    }
}
