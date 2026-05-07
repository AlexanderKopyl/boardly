<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\Exception\InvalidCredentials;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationExceptionMapper implements ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int
    {
        return 500;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof InvalidCredentials
            || $exception instanceof AccountNotActive;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        if ($exception instanceof InvalidCredentials) {
            return new JsonResponse(
                ['error' => ['code' => 'invalid_credentials', 'message' => 'Invalid credentials.']],
                JsonResponse::HTTP_UNAUTHORIZED,
            );
        }

        if ($exception instanceof AccountNotActive) {
            return new JsonResponse(
                ['error' => ['code' => 'account_not_active', 'message' => 'Account is not active.']],
                JsonResponse::HTTP_FORBIDDEN,
            );
        }

        throw new \LogicException(sprintf('Unsupported exception "%s".', $exception::class));
    }
}
