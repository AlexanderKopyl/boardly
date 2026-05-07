<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Application\Security\VerifiedAccessToken;

interface AccessTokenVerifierInterface
{
    public function verify(string $token): VerifiedAccessToken;
}
