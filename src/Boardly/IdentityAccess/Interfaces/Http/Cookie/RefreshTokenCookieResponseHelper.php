<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Cookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class RefreshTokenCookieResponseHelper
{
    private const string REFRESH_COOKIE_NAME = 'refresh_token';

    public function clearRefreshTokenCookie(Response $response): void
    {
        $response->headers->clearCookie(
            self::REFRESH_COOKIE_NAME,
            '/api/auth',
            null,
            true,
            true,
            Cookie::SAMESITE_NONE,
        );
    }
}
