<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Event;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class AccountRejected
{
    public function __construct(
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $rejectedAt,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function rejectedAt(): \DateTimeImmutable
    {
        return $this->rejectedAt;
    }
}
