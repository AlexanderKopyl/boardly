<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
