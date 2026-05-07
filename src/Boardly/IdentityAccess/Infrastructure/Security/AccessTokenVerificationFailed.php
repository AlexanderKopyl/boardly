<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

final class AccessTokenVerificationFailed extends \RuntimeException
{
    public static function invalid(): self
    {
        return new self('Access token is invalid or expired.');
    }
}
