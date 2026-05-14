<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshAuthentication;

use App\Boardly\IdentityAccess\Application\Exception\InvalidRefreshToken;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\RefreshSession\RefreshSessionRotator;
use App\Boardly\IdentityAccess\Application\Security\AccessToken;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class RefreshAuthenticationHandler
{
    public function __construct(
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshSessionRotator $refreshSessionRotator,
        private TransactionalInterface $transactional,
    ) {
    }

    public function __invoke(RefreshAuthenticationCommand $command): RefreshAuthenticationResult
    {
        $rawRefreshToken = trim($command->rawRefreshToken());

        if ('' === $rawRefreshToken) {
            throw InvalidRefreshToken::create();
        }

        $invalidRefreshToken = false;

        $result = $this->transactional->transactional(function () use ($rawRefreshToken, &$invalidRefreshToken): ?RefreshAuthenticationResult {
            $accessToken = null;
            try {
                $rotation = $this->refreshSessionRotator->rotate(
                    $rawRefreshToken,
                    function (AccountId $accountId, \DateTimeImmutable $now) use (&$accessToken): void {
                        $accessToken = $this->accessTokenIssuer->issueForAccount($accountId, $now);
                    },
                );
            } catch (InvalidRefreshToken) {
                $invalidRefreshToken = true;

                return null;
            }

            if (!$accessToken instanceof AccessToken) {
                throw new \LogicException('Access token was not issued during refresh session rotation.');
            }

            return new RefreshAuthenticationResult(
                $accessToken->token(),
                $accessToken->expiresAt(),
                $accessToken->expiresInSeconds(),
                $rotation->rawRefreshToken(),
                $rotation->refreshTokenExpiresAt(),
            );
        });

        if ($invalidRefreshToken) {
            throw InvalidRefreshToken::create();
        }

        if (!$result instanceof RefreshAuthenticationResult) {
            throw new \LogicException('Refresh authentication transaction did not return a result.');
        }

        return $result;
    }
}
