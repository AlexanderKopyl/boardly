<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Interfaces\Http\Exception\ApiExceptionMapperInterface;
use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProjectNotFoundExceptionMapper implements ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int
    {
        return 400;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ProjectNotFound;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        return new JsonResponse(
            [
                'error' => [
                    'code' => 'project_not_found',
                    'message' => 'Project not found.',
                ],
            ],
            JsonResponse::HTTP_NOT_FOUND,
        );
    }
}
