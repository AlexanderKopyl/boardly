<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;

interface RefreshTokenHasherInterface
{
    public function hash(string $rawRefreshToken): RefreshTokenHash;
}
