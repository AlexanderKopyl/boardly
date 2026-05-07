<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\Exception\InvalidCredentials;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordVerifierInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class AuthenticateAccountHandler
{
    private const string REFRESH_TOKEN_TTL = '+30 days';

    public function __construct(
        private AccountRepositoryInterface $accounts,
        private PasswordVerifierInterface $passwordVerifier,
        private AccessTokenIssuerInterface $accessTokenIssuer,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private TransactionalInterface $transactional,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
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
            $refreshToken = $this->refreshTokenGenerator->generate();
            $refreshTokenHash = $this->refreshTokenHasher->hash($refreshToken);
            $refreshTokenExpiresAt = $now->modify(self::REFRESH_TOKEN_TTL);

            $this->refreshSessions->save(RefreshSession::create(
                RefreshSessionId::fromString($this->idGenerator->generate()),
                $account->id(),
                $refreshTokenHash,
                RefreshSessionFamilyId::fromString($this->idGenerator->generate()),
                $refreshTokenExpiresAt,
                $now,
            ));

            return new AuthenticateAccountResult(
                $accessToken->token(),
                $accessToken->expiresAt(),
                $accessToken->expiresInSeconds(),
                $refreshToken,
                $refreshTokenExpiresAt,
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
