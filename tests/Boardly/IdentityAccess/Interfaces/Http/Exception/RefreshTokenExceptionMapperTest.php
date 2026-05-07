<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Application\Exception\InvalidRefreshToken;
use App\Boardly\IdentityAccess\Interfaces\Http\Cookie\RefreshTokenCookieResponseHelper;
use App\Boardly\IdentityAccess\Interfaces\Http\Exception\RefreshTokenExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenExceptionMapperTest extends TestCase
{
    public function testInvalidRefreshTokenReturns401AndClearsRefreshCookie(): void
    {
        $mapper = new RefreshTokenExceptionMapper(new RefreshTokenCookieResponseHelper());

        $response = $mapper->map(InvalidRefreshToken::create(), Request::create('/api/auth/refresh'));
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $cookie = $this->refreshCookie($response->headers->getCookies());

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('invalid_refresh_token', $data['error']['code']);
        self::assertSame('Invalid refresh token.', $data['error']['message']);
        self::assertContains($cookie->getValue(), [null, '']);
        self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/api/auth', $cookie->getPath());
    }

    /**
     * @param list<Cookie> $cookies
     */
    private function refreshCookie(array $cookies): Cookie
    {
        foreach ($cookies as $cookie) {
            if ('refresh_token' === $cookie->getName()) {
                return $cookie;
            }
        }

        self::fail('The response did not clear a refresh_token cookie.');
    }
}
