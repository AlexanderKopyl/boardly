<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Event;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class AccountDisabled
{
    public function __construct(
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $disabledAt,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function disabledAt(): \DateTimeImmutable
    {
        return $this->disabledAt;
    }
}
