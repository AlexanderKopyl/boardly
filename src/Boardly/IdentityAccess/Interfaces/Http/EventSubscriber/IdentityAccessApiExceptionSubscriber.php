<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\EventSubscriber;

use App\Boardly\IdentityAccess\Application\Exception\EmailAlreadyRegistered;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountName;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class IdentityAccessApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof EmailAlreadyRegistered) {
            $event->setResponse(new JsonResponse(
                ['error' => ['code' => 'email_already_registered', 'message' => 'Email is already registered.']],
                JsonResponse::HTTP_CONFLICT,
            ));
            return;
        }

        if ($exception instanceof HttpException && $exception->getPrevious() instanceof ValidationFailedException) {
            $violations = [];
            foreach ($exception->getPrevious()->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            $event->setResponse(new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'The request payload is invalid.',
                        'violations' => $violations,
                    ],
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            ));
            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(new JsonResponse(
                ['error' => ['code' => 'invalid_request', 'message' => 'Invalid request body.']],
                JsonResponse::HTTP_BAD_REQUEST,
            ));
            return;
        }

        if ($exception instanceof InvalidEmail || $exception instanceof InvalidAccountName) {
            $event->setResponse(new JsonResponse(
                [
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'The request payload is invalid.',
                        'violations' => [],
                    ],
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            ));
        }
    }
}