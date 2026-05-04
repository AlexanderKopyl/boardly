<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Message;

final readonly class AccountRegisteredMessage
{
    public function __construct(
        public string $outboxId,
        public string $eventId,
        public string $accountId,
        public \DateTimeImmutable $registeredAt,
        public bool $isSystemAdmin,
    ) {
    }
}
