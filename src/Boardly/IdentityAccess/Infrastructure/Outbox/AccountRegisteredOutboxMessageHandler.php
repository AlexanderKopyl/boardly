<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Diagnostic consumer used to verify the transactional outbox publishing flow.
 *
 * This handler intentionally performs no business side effect. Real consumers
 * such as notifications, audit projections, or search indexing should be added
 * as separate handlers when those features exist.
 */
#[AsMessageHandler(bus: 'event.bus')]
final readonly class AccountRegisteredOutboxMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionalInterface $transactional,
        private ProcessedMessageStoreInterface $processedMessages,
    ) {
    }

    public function __invoke(AccountRegisteredMessage $message): void
    {
        $this->transactional->transactional(function () use ($message): void {
            if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
                return;
            }

            $this->logger->info('AccountRegistered outbox message consumed.', [
                'outbox_id' => $message->outboxId,
                'event_id' => $message->eventId,
                'account_id' => $message->accountId,
                'registered_at' => $message->registeredAt->format(\DateTimeInterface::ATOM),
                'is_system_admin' => $message->isSystemAdmin,
            ]);

            $this->processedMessages->markProcessed($message->eventId, self::class);
        });
    }
}
