<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

interface AccessTokenVerifierInterface
{
    public function verify(string $token): VerifiedAccessToken;
}
