<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

final class AuthenticationFailureResponseFactory
{
    public function create(): JsonResponse
    {
        return new JsonResponse(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            JsonResponse::HTTP_UNAUTHORIZED,
        );
    }
}
