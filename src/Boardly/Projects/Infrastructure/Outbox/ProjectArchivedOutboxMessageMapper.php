<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectArchivedMessage;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface;
use App\Shared\Infrastructure\Outbox\OutboxRecord;

final readonly class ProjectArchivedOutboxMessageMapper implements OutboxMessageMapperInterface
{
    private const EVENT_TYPE = 'projects.project_archived';

    public function supports(OutboxRecord $record): bool
    {
        return self::EVENT_TYPE === $record->eventType;
    }

    public function map(OutboxRecord $record): object
    {
        $projectId = $this->payloadNonEmptyString($record, 'project_id');

        if (null !== $record->aggregateId && $record->aggregateId !== $projectId) {
            throw new \InvalidArgumentException(sprintf(
                'Project archived outbox record aggregateId "%s" must match payload project_id "%s".',
                $record->aggregateId,
                $projectId,
            ));
        }

        return new ProjectArchivedMessage(
            outboxId: $record->id,
            eventId: $record->eventId,
            projectId: $projectId,
            archivedAt: $this->payloadDateTime($record, 'archived_at'),
        );
    }

    private function payloadNonEmptyString(OutboxRecord $record, string $key): string
    {
        $value = $record->payload[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf(
                'Project archived outbox record payload field "%s" must be a non-empty string.',
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
                'Project archived outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException(sprintf(
                'Project archived outbox record payload field "%s" must be a valid date/time string.',
                $key,
            ), previous: $exception);
        }
    }
}
