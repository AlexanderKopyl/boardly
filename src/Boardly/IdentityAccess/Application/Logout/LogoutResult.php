<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Logout;

final readonly class LogoutResult
{
    private function __construct()
    {
    }

    public static function success(): self
    {
        return new self();
    }
}
