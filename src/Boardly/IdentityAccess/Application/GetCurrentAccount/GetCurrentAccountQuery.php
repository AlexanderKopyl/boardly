<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\GetCurrentAccount;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final readonly class GetCurrentAccountQuery
{
    public function __construct(
        private AccountId $accountId,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }
}
