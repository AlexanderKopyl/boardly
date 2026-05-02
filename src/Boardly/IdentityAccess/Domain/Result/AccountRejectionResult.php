<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountRejected;

final readonly class AccountRejectionResult
{
    public function __construct(
        private AccountRejected $event,
    ) {
    }

    public function event(): AccountRejected
    {
        return $this->event;
    }
}
