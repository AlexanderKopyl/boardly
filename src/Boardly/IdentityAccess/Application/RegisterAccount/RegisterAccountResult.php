<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RegisterAccount;

final readonly class RegisterAccountResult
{
    public function __construct(
        private string $accountId,
        private string $status,
    ) {
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function status(): string
    {
        return $this->status;
    }
}
