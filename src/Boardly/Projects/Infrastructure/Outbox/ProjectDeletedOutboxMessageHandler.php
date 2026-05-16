<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectDeletedMessage;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProjectDeletedOutboxMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionalInterface $transactional,
        private ProcessedMessageStoreInterface $processedMessages,
    ) {
    }

    public function __invoke(ProjectDeletedMessage $message): void
    {
        $this->transactional->transactional(function () use ($message): void {
            if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
                return;
            }

            $this->logger->info('ProjectDeleted outbox message consumed.', [
                'outbox_id' => $message->outboxId,
                'event_id' => $message->eventId,
                'project_id' => $message->projectId,
                'deleted_at' => $message->deletedAt->format(DATE_ATOM),
            ]);

            $this->processedMessages->markProcessed($message->eventId, self::class);
        });
    }
}
