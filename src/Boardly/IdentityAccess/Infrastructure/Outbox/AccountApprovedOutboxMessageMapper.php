<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface;
use App\Shared\Infrastructure\Outbox\OutboxRecord;

final readonly class AccountApprovedOutboxMessageMapper implements OutboxMessageMapperInterface
{
    private const EVENT_TYPE = 'identity_access.account_approved';

    public function supports(OutboxRecord $record): bool
    {
        return self::EVENT_TYPE === $record->eventType;
    }

    public function map(OutboxRecord $record): object
    {
        $accountId = $this->payloadNonEmptyString($record, 'account_id');

        if (null !== $record->aggregateId && $record->aggregateId !== $accountId) {
            throw new \InvalidArgumentException(sprintf(
                'Account approval outbox record aggregateId "%s" must match payload account_id "%s".',
                $record->aggregateId,
                $accountId,
            ));
        }

        return new AccountApprovedMessage(
            outboxId: $record->id,
            eventId: $record->eventId,
            accountId: $accountId,
            approvedAt: $this->payloadDateTime($record, 'approved_at'),
        );
    }

    private function payloadNonEmptyString(OutboxRecord $record, string $key): string
    {
        $value = $record->payload[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Account approval outbox record payload field "%s" must be a non-empty string.',
                $key,
            ));
        }

        return $value;
    }

    private function payloadDateTime(OutboxRecord $record, string $key): \DateTimeImmutable
    {
        $value = $record->payload[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Account approval outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(sprintf(
                'Account approval outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ), previous: $exception);
        }
    }
}
