<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\EventSubscriber;

use App\Boardly\IdentityAccess\Interfaces\Http\Exception\ApiExceptionMapperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class IdentityAccessApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @param iterable<ApiExceptionMapperInterface> $exceptionMappers
     */
    public function __construct(
        private readonly iterable $exceptionMappers,
    ) {
    }

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
        foreach ($this->exceptionMappers as $mapper) {
            if (!$mapper->supports($exception)) {
                continue;
            }

            $event->setResponse($mapper->map($exception, $event->getRequest()));
            return;
        }
    }
}
