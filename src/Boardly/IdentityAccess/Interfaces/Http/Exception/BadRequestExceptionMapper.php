<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class BadRequestExceptionMapper implements ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int
    {
        return 100;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof BadRequestHttpException;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        return new JsonResponse(
            ['error' => ['code' => 'invalid_request', 'message' => 'Invalid request body.']],
            JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}
