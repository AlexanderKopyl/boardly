<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Application\Exception\InvalidRefreshToken;
use App\Boardly\IdentityAccess\Interfaces\Http\Cookie\RefreshTokenCookieResponseHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RefreshTokenExceptionMapper implements ApiExceptionMapperInterface
{
    public function __construct(
        private RefreshTokenCookieResponseHelper $refreshTokenCookieResponseHelper,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return 400;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InvalidRefreshToken;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        $response = new JsonResponse(
            ['error' => ['code' => 'invalid_refresh_token', 'message' => 'Invalid refresh token.']],
            JsonResponse::HTTP_UNAUTHORIZED,
        );
        $this->refreshTokenCookieResponseHelper->clearRefreshTokenCookie($response);

        return $response;
    }
}
