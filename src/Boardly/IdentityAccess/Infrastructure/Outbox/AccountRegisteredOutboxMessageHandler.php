<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use App\Shared\Infrastructure\Outbox\OutboxMessage;
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
    private const EVENT_TYPE = 'identity_access.account_registered';

    public function __construct(
        private LoggerInterface $logger,
        private TransactionalInterface $transactional,
        private ProcessedMessageStoreInterface $processedMessages,
    ) {
    }

    public function __invoke(OutboxMessage $message): void
    {
        if (self::EVENT_TYPE !== $message->eventType) {
            return;
        }

        $this->transactional->transactional(function () use ($message): void {
            if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
                $this->logger->debug('Duplicate AccountRegistered outbox message skipped.', [
                    'event_id' => $message->eventId,
                    'handler' => self::class,
                ]);

                return;
            }

            $this->logger->info('AccountRegistered outbox message consumed.', [
                'outbox_id' => $message->outboxId,
                'event_id' => $message->eventId,
                'event_type' => $message->eventType,
                'aggregate_type' => $message->aggregateType,
                'aggregate_id' => $message->aggregateId,
                'account_id' => $this->payloadString($message, 'account_id'),
                'registered_at' => $this->payloadString($message, 'registered_at'),
                'is_system_admin' => $this->payloadBool($message, 'is_system_admin'),
                'email_hash' => $this->payloadSha256($message, 'email'),
            ]);

            $this->processedMessages->markProcessed($message->eventId, self::class);
        });
    }

    private function payloadString(OutboxMessage $message, string $key): string
    {
        $value = $message->payload[$key] ?? null;

        if (!is_string($value) || '' === $value) {
            throw new \UnexpectedValueException(sprintf(
                'AccountRegistered outbox message payload field "%s" must be a non-empty string.',
                $key,
            ));
        }

        return $value;
    }

    private function payloadBool(OutboxMessage $message, string $key): bool
    {
        $value = $message->payload[$key] ?? null;

        if (!is_bool($value)) {
            throw new \UnexpectedValueException(sprintf(
                'AccountRegistered outbox message payload field "%s" must be a boolean.',
                $key,
            ));
        }

        return $value;
    }

    private function payloadSha256(OutboxMessage $message, string $key): string
    {
        return hash('sha256', $this->payloadString($message, $key));
    }
}
