<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Application\Exception\EmailAlreadyRegistered;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationExceptionMapper implements ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int
    {
        return 300;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof EmailAlreadyRegistered;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        return new JsonResponse(
            ['error' => ['code' => 'email_already_registered', 'message' => 'Email is already registered.']],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
