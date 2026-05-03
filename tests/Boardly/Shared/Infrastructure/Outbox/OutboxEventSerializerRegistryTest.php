<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerRegistry;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use PHPUnit\Framework\TestCase;

final class OutboxEventSerializerRegistryTest extends TestCase
{
    public function testDelegatesToMatchingSerializer(): void
    {
        $event = new SupportedTestDomainEvent();
        $createdAt = new \DateTimeImmutable('2026-05-03T10:16:00+00:00');
        $expected = $this->serializedEvent($createdAt);
        $nonMatching = new TestOutboxEventSerializer(false, $expected);
        $matching = new TestOutboxEventSerializer(true, $expected);

        $serialized = (new OutboxEventSerializerRegistry([$nonMatching, $matching]))->serialize($event, $createdAt);

        self::assertSame($expected, $serialized);
        self::assertSame([$event], $nonMatching->supportedEvents);
        self::assertSame([$event], $matching->supportedEvents);
        self::assertSame([$event], $matching->serializedEvents);
        self::assertSame([$createdAt], $matching->serializedNowValues);
    }

    public function testUnsupportedDomainEventThrowsClearException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported domain event');
        $this->expectExceptionMessage(UnsupportedTestDomainEvent::class);

        (new OutboxEventSerializerRegistry([]))->serialize(new UnsupportedTestDomainEvent());
    }

    public function testRegistryDoesNotRequireConcreteAccountRegisteredDependency(): void
    {
        $registry = new OutboxEventSerializerRegistry([]);

        self::assertInstanceOf(OutboxEventSerializerRegistry::class, $registry);
    }

    private function serializedEvent(\DateTimeImmutable $createdAt): SerializedOutboxEvent
    {
        return new SerializedOutboxEvent(
            eventId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            eventType: 'test.event',
            aggregateType: 'test.aggregate',
            aggregateId: 'test-aggregate-id',
            payload: ['value' => 'test'],
            occurredAt: new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
            availableAt: new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
            createdAt: $createdAt,
        );
    }
}

final class SupportedTestDomainEvent implements DomainEvent
{
}

final class UnsupportedTestDomainEvent implements DomainEvent
{
}

final class TestOutboxEventSerializer implements OutboxEventSerializerInterface
{
    /**
     * @var list<DomainEvent>
     */
    public array $supportedEvents = [];

    /**
     * @var list<DomainEvent>
     */
    public array $serializedEvents = [];

    /**
     * @var list<?\DateTimeImmutable>
     */
    public array $serializedNowValues = [];

    public function __construct(
        private readonly bool $supports,
        private readonly SerializedOutboxEvent $serialized,
    ) {
    }

    public function supports(DomainEvent $event): bool
    {
        $this->supportedEvents[] = $event;

        return $this->supports;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        $this->serializedEvents[] = $event;
        $this->serializedNowValues[] = $now;

        return $this->serialized;
    }
}
