<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use Symfony\Component\Uid\Uuid;

final class AccountRegisteredOutboxEventSerializer implements OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool
    {
        return $event instanceof AccountRegistered;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        if (!$event instanceof AccountRegistered) {
            throw new \InvalidArgumentException(sprintf(
                'Expected domain event "%s", got "%s".',
                AccountRegistered::class,
                $event::class,
            ));
        }

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
            createdAt: $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
