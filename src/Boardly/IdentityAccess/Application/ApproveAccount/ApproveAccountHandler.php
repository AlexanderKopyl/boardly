<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\ApproveAccount;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class ApproveAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
        private OutboxInterface $outbox,
    ) {
    }

    public function __invoke(ApproveAccountCommand $command): ApproveAccountResult
    {
        return $this->transactional->transactional(
            function () use ($command): ApproveAccountResult {
                $email = Email::fromString($command->email());
                $account = $this->accounts->findByEmail($email);

                if (null === $account) {
                    throw new AccountNotFound('Account was not found.');
                }

                $approvedAt = $this->clock->now();
                $domainResult = $account->approve($approvedAt);

                $this->accounts->save($account);
                $this->outbox->store([$domainResult->event()]);

                return new ApproveAccountResult(
                    $account->id()->value(),
                    $account->email()->value(),
                    $account->status()->value(),
                    $approvedAt,
                );
            },
        );
    }
}
