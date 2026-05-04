<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface;
use App\Shared\Infrastructure\Outbox\OutboxRecord;

final readonly class AccountRegisteredOutboxMessageMapper implements OutboxMessageMapperInterface
{
    private const EVENT_TYPE = 'identity_access.account_registered';

    public function supports(OutboxRecord $record): bool
    {
        return self::EVENT_TYPE === $record->eventType;
    }

    public function map(OutboxRecord $record): object
    {
        $accountId = $this->payloadNonEmptyString($record, 'account_id');

        if (null !== $record->aggregateId && $record->aggregateId !== $accountId) {
            throw new \InvalidArgumentException(sprintf(
                'Account registration outbox record aggregateId "%s" must match payload account_id "%s".',
                $record->aggregateId,
                $accountId,
            ));
        }

        return new AccountRegisteredMessage(
            outboxId: $record->id,
            eventId: $record->eventId,
            accountId: $accountId,
            registeredAt: $this->payloadDateTime($record, 'registered_at'),
            isSystemAdmin: $this->payloadBool($record, 'is_system_admin'),
        );
    }

    private function payloadNonEmptyString(OutboxRecord $record, string $key): string
    {
        $value = $record->payload[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Account registration outbox record payload field "%s" must be a non-empty string.',
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
                'Account registration outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(sprintf(
                'Account registration outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ), previous: $exception);
        }
    }

    private function payloadBool(OutboxRecord $record, string $key): bool
    {
        $value = $record->payload[$key] ?? null;

        if (!is_bool($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Account registration outbox record payload field "%s" must be a boolean.',
                $key,
            ));
        }

        return $value;
    }
}
