<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\Exception\InvalidCredentials;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordVerifierInterface;
use App\Boardly\IdentityAccess\Application\RefreshSession\RefreshSessionIssuer;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class AuthenticateAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
        private PasswordVerifierInterface $passwordVerifier,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshSessionIssuer $refreshSessionIssuer,
        private TransactionalInterface $transactional,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(AuthenticateAccountCommand $command): AuthenticateAccountResult
    {
        try {
            $email = Email::fromString($command->email());
        } catch (InvalidEmail) {
            throw InvalidCredentials::create();
        }

        $account = $this->accounts->findByEmail($email);

        if (null === $account) {
            throw InvalidCredentials::create();
        }

        if (!$this->passwordVerifier->verify($command->plainPassword(), $account->passwordHash()->value())) {
            throw InvalidCredentials::create();
        }

        if (!$account->status()->isActive()) {
            throw AccountNotActive::create();
        }

        return $this->transactional->transactional(function () use ($account): AuthenticateAccountResult {
            $now = $this->clock->now();
            $accessToken = $this->accessTokenIssuer->issueForAccount($account->id(), $now);
            $refreshSession = $this->refreshSessionIssuer->issueForAccount($account->id());

            return new AuthenticateAccountResult(
                $accessToken->token(),
                $accessToken->expiresAt(),
                $accessToken->expiresInSeconds(),
                $refreshSession->rawRefreshToken(),
                $refreshSession->refreshTokenExpiresAt(),
                new AuthenticatedAccount(
                    $account->id()->value(),
                    $account->email()->value(),
                    $account->name()->value(),
                    $account->status()->value(),
                ),
            );
        });
    }
}
