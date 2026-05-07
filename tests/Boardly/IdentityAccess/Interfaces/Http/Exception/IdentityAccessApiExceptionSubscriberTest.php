<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Exception;

use App\Boardly\IdentityAccess\Interfaces\Http\EventSubscriber\IdentityAccessApiExceptionSubscriber;
use App\Boardly\IdentityAccess\Interfaces\Http\Exception\ApiExceptionMapperInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class IdentityAccessApiExceptionSubscriberTest extends TestCase
{
    public function testDelegatesToFirstSupportedMapperForApiRequests(): void
    {
        $exception = new \RuntimeException('Expected test exception.');
        $unsupportedMapper = new RecordingMapper(false, new JsonResponse(['unexpected' => true], 500));
        $supportedMapper = new RecordingMapper(true, new JsonResponse(['ok' => true], 418));
        $skippedMapper = new RecordingMapper(true, new JsonResponse(['skipped' => true], 500));
        $event = $this->exceptionEvent('/api/auth/login', $exception);

        $subscriber = new IdentityAccessApiExceptionSubscriber([
            $unsupportedMapper,
            $supportedMapper,
            $skippedMapper,
        ]);

        $subscriber->onKernelException($event);

        self::assertSame($exception, $unsupportedMapper->supportedException);
        self::assertSame($exception, $supportedMapper->supportedException);
        self::assertNull($skippedMapper->supportedException);
        self::assertSame(418, $event->getResponse()?->getStatusCode());
        self::assertSame('{"ok":true}', $event->getResponse()?->getContent());
    }

    public function testIgnoresNonApiRequests(): void
    {
        $mapper = new RecordingMapper(true, new JsonResponse(['ok' => true], 418));
        $event = $this->exceptionEvent('/health', new \RuntimeException('Expected test exception.'));

        $subscriber = new IdentityAccessApiExceptionSubscriber([$mapper]);

        $subscriber->onKernelException($event);

        self::assertNull($mapper->supportedException);
        self::assertFalse($event->hasResponse());
    }

    private function exceptionEvent(string $path, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}

final class RecordingMapper implements ApiExceptionMapperInterface
{
    public ?\Throwable $supportedException = null;

    public function __construct(
        private readonly bool $supports,
        private readonly JsonResponse $response,
    ) {
    }

    public static function getDefaultPriority(): int
    {
        return 0;
    }

    public function supports(\Throwable $exception): bool
    {
        $this->supportedException = $exception;

        return $this->supports;
    }

    public function map(\Throwable $exception, Request $request): JsonResponse
    {
        return $this->response;
    }
}
