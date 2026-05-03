<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RegisterAccount;

use App\Boardly\IdentityAccess\Application\Exception\EmailAlreadyRegistered;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class RegisterAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
        private PasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
        private TransactionalInterface $transactional,
    ) {
    }

    public function __invoke(RegisterAccountCommand $command): RegisterAccountResult
    {
        return $this->transactional->transactional(
            function () use ($command): RegisterAccountResult {
                $email = Email::fromString($command->email());
                $name = AccountName::fromString($command->name());

                if ($this->accounts->existsByEmail($email)) {
                    throw EmailAlreadyRegistered::withEmail($email);
                }

                $passwordHash = PasswordHash::fromString(
                    $this->passwordHasher->hash($command->plainPassword()),
                );
                $id = AccountId::fromString($this->idGenerator->generate());
                $now = $this->clock->now();

                $domainResult = Account::register($id, $email, $passwordHash, $name, $now);
                $account = $domainResult->account();

                $this->accounts->save($account);

                return new RegisterAccountResult(
                    $account->id()->value(),
                    $account->status()->value(),
                );
            },
        );
    }
}
