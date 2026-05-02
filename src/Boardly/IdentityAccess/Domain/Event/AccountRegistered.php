<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Event;

use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class AccountRegistered
{
    public function __construct(
        private readonly AccountId $accountId,
        private readonly Email $email,
        private readonly bool $isSystemAdmin,
        private readonly \DateTimeImmutable $registeredAt,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function isSystemAdmin(): bool
    {
        return $this->isSystemAdmin;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
