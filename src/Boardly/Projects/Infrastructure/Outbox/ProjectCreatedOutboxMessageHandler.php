<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectCreatedMessage;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProjectCreatedOutboxMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionalInterface $transactional,
        private ProcessedMessageStoreInterface $processedMessages,
    ) {
    }

    public function __invoke(ProjectCreatedMessage $message): void
    {
        $this->transactional->transactional(function () use ($message): void {
            if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
                return;
            }

            $this->logger->info('ProjectCreated outbox message consumed.', [
                'outbox_id' => $message->outboxId,
                'event_id' => $message->eventId,
                'project_id' => $message->projectId,
                'owner_account_id' => $message->ownerAccountId,
                'name' => $message->name,
                'icon_key' => $message->iconKey,
                'created_at' => $message->createdAt->format(DATE_ATOM),
            ]);

            $this->processedMessages->markProcessed($message->eventId, self::class);
        });
    }
}
