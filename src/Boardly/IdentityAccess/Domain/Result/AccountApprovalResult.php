<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;

final readonly class AccountApprovalResult
{
    public function __construct(
        private AccountApproved $event,
    ) {
    }

    public function event(): AccountApproved
    {
        return $this->event;
    }
}
