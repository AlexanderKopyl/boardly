<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

final readonly class AuthenticatedAccount
{
    public function __construct(
        private string $id,
        private string $email,
        private string $name,
        private string $status,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): string
    {
        return $this->status;
    }
}
