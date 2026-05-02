<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Model\Account;

final readonly class AccountRegistrationResult
{
    public function __construct(
        private Account $account,
        private AccountRegistered $event,
    ) {
    }

    public function account(): Account
    {
        return $this->account;
    }

    public function event(): AccountRegistered
    {
        return $this->event;
    }
}
