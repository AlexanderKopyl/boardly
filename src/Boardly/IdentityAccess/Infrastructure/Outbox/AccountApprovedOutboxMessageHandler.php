<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class AccountApprovedOutboxMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionalInterface $transactional,
        private ProcessedMessageStoreInterface $processedMessages,
    ) {
    }

    public function __invoke(AccountApprovedMessage $message): void
    {
        $this->transactional->transactional(function () use ($message): void {
            if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
                return;
            }

            $this->logger->info('IdentityAccess account approved.', [
                'event_id' => $message->eventId,
                'outbox_id' => $message->outboxId,
                'account_id' => $message->accountId,
                'approved_at' => $message->approvedAt->format(DATE_ATOM),
            ]);

            $this->processedMessages->markProcessed($message->eventId, self::class);
        });
    }
}
