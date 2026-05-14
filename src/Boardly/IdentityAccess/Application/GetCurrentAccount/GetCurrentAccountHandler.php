<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\GetCurrentAccount;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetCurrentAccountHandler
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
    ) {
    }

    public function __invoke(GetCurrentAccountQuery $query): GetCurrentAccountResult
    {
        $account = $this->accounts->find($query->accountId());

        if (!$account instanceof \App\Boardly\IdentityAccess\Domain\Model\Account) {
            throw AccountNotActive::create();
        }

        if (!$account->status()->isActive()) {
            throw AccountNotActive::create();
        }

        return new GetCurrentAccountResult(
            $account->id()->value(),
            $account->email()->value(),
            $account->name()->value(),
            $account->status()->value(),
        );
    }
}
