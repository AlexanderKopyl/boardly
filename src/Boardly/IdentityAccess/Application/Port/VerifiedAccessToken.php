<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final readonly class VerifiedAccessToken
{
    public function __construct(
        private AccountId $accountId,
        private string $tokenId,
        private \DateTimeImmutable $issuedAt,
        private \DateTimeImmutable $expiresAt,
    ) {
        if ('' === trim($this->tokenId)) {
            throw new \InvalidArgumentException('Access token id cannot be empty.');
        }

        if ($this->expiresAt <= $this->issuedAt) {
            throw new \InvalidArgumentException('Access token expiration must be after issue time.');
        }
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function tokenId(): string
    {
        return $this->tokenId;
    }

    public function issuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
