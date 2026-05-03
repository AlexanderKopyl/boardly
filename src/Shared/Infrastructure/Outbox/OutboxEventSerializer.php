<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final class OutboxEventSerializer
{
    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        return match (true) {
            $event instanceof AccountRegistered => $this->serializeAccountRegistered(
                $event,
                $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            ),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported domain event "%s" cannot be serialized for the outbox.',
                $event::class,
            )),
        };
    }

    private function serializeAccountRegistered(
        AccountRegistered $event,
        \DateTimeImmutable $createdAt,
    ): SerializedOutboxEvent {
        $accountId = $event->accountId()->value();
        $registeredAt = $event->registeredAt();

        return new SerializedOutboxEvent(
            eventId: Uuid::v7()->toRfc4122(),
            eventType: 'identity_access.account_registered',
            aggregateType: 'identity_access.account',
            aggregateId: $accountId,
            payload: [
                'account_id' => $accountId,
                'email' => $event->email()->value(),
                'is_system_admin' => $event->isSystemAdmin(),
                'registered_at' => $registeredAt->format(\DateTimeInterface::ATOM),
            ],
            occurredAt: $registeredAt,
            availableAt: $registeredAt,
            createdAt: $createdAt,
        );
    }
}
