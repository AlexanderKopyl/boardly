<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\CreateSystemAdmin;

final readonly class CreateSystemAdminResult
{
    public function __construct(
        private string $accountId,
        private string $email,
        private string $status,
        private bool $isSystemAdmin,
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

    public function isSystemAdmin(): bool
    {
        return $this->isSystemAdmin;
    }
}
