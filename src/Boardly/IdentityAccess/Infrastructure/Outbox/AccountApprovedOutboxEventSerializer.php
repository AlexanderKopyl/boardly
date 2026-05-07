<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use Symfony\Component\Uid\Uuid;

final class AccountApprovedOutboxEventSerializer implements OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool
    {
        return $event instanceof AccountApproved;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        /** @var AccountApproved $event */
        $accountId = $event->accountId()->value();
        $approvedAt = $event->approvedAt();

        return new SerializedOutboxEvent(
            eventId: Uuid::v7()->toRfc4122(),
            eventType: 'identity_access.account_approved',
            aggregateType: 'identity_access.account',
            aggregateId: $accountId,
            payload: [
                'account_id' => $accountId,
                'approved_at' => $approvedAt->format(\DateTimeInterface::ATOM),
            ],
            occurredAt: $approvedAt,
            availableAt: $approvedAt,
            createdAt: $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
