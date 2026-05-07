<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Security;

final readonly class AccessToken
{
    public function __construct(
        private string $token,
        private \DateTimeImmutable $expiresAt,
        private int $expiresInSeconds,
    ) {
        if ('' === trim($this->token)) {
            throw new \InvalidArgumentException('Access token cannot be empty.');
        }

        if ($this->expiresInSeconds <= 0) {
            throw new \InvalidArgumentException('Access token TTL must be positive.');
        }
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function expiresInSeconds(): int
    {
        return $this->expiresInSeconds;
    }
}
