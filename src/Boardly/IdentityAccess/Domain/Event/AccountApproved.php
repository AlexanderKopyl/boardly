<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Event;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class AccountApproved
{
    public function __construct(
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $approvedAt,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function approvedAt(): \DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
