<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\ApproveAccount;

final readonly class ApproveAccountCommand
{
    public function __construct(
        private string $email,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }
}
