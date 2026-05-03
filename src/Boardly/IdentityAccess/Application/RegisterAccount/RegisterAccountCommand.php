<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RegisterAccount;

final readonly class RegisterAccountCommand
{
    public function __construct(
        private string $email,
        private string $plainPassword,
        private string $name,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function plainPassword(): string
    {
        return $this->plainPassword;
    }

    public function name(): string
    {
        return $this->name;
    }
}
