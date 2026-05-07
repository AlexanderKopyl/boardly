<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

interface RefreshTokenGeneratorInterface
{
    public function generate(): string;
}
