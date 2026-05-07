<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

interface PasswordVerifierInterface
{
    public function verify(string $plainPassword, string $passwordHash): bool;
}
