<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

final readonly class OutboxPublishResult
{
    public function __construct(
        public int $selected,
        public int $published,
        public int $failed,
        public int $skipped,
    ) {
    }

    public static function zero(): self
    {
        return new self(0, 0, 0, 0);
    }
}
