<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

final readonly class AuthenticateAccountCommand
{
    public function __construct(
        private string $email,
        private string $plainPassword,
        private ?string $userAgent,
        private ?string $ipAddress,
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

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }
}
