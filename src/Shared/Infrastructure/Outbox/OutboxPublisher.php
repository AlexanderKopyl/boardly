<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Symfony\Component\Messenger\MessageBusInterface;

final readonly class OutboxPublisher
{
    public function __construct(
        private DoctrineOutbox $outbox,
        private OutboxMessageMapperRegistry $messageMapperRegistry,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function publish(int $limit, ?\DateTimeImmutable $now = null): OutboxPublishResult
    {
        if ($limit < 1) {
            return OutboxPublishResult::zero();
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $records = $this->outbox->loadUnpublished($limit, $now);

        $published = 0;
        $failed = 0;

        foreach ($records as $record) {
            try {
                $message = $this->messageMapperRegistry->map($record);
                $this->messageBus->dispatch($message);
            } catch (\Throwable $exception) {
                $this->outbox->recordFailure(
                    $record->id,
                    $exception->getMessage(),
                    $now->modify('+60 seconds'),
                );
                ++$failed;
                continue;
            }

            $this->outbox->markPublished($record->id, $now);
            ++$published;
        }

        return new OutboxPublishResult(\count($records), $published, $failed, 0);
    }
}
