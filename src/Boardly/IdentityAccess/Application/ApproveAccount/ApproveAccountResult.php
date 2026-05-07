<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\ApproveAccount;

final readonly class ApproveAccountResult
{
    public function __construct(
        private string $accountId,
        private string $email,
        private string $status,
        private \DateTimeImmutable $approvedAt,
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function approvedAt(): \DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
