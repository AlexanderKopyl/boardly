<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountName;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ValidationExceptionMapper implements ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int
    {
        return 200;
    }

    public function supports(\Throwable $exception): bool
    {
        return ($exception instanceof HttpException && $exception->getPrevious() instanceof ValidationFailedException)
            || $exception instanceof InvalidEmail
            || $exception instanceof InvalidAccountName;
    }

    public function map(\Throwable $exception, Request $request): Response
    {
        return new JsonResponse(
            [
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'The request payload is invalid.',
                    'violations' => $this->violations($exception),
                ],
            ],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    private function violations(\Throwable $exception): array
    {
        if (!$exception instanceof HttpException) {
            return [];
        }

        $previous = $exception->getPrevious();
        if (!$previous instanceof ValidationFailedException) {
            return [];
        }

        $violations = [];
        foreach ($previous->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
