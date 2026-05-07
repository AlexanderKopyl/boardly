<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

final readonly class RefreshSessionRevocationResult
{
    public function __construct(
        private bool $revoked,
    ) {
    }

    public static function revoked(): self
    {
        return new self(true);
    }

    public static function idempotentSuccess(): self
    {
        return new self(false);
    }

    public function revokedSession(): bool
    {
        return $this->revoked;
    }
}
