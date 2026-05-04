<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class RefreshSession
{
    private function __construct(
        private readonly RefreshSessionId $id,
        private readonly AccountId $accountId,
        private readonly RefreshTokenHash $tokenHash,
        private readonly RefreshSessionFamilyId $familyId,
        private readonly \DateTimeImmutable $expiresAt,
        private ?\DateTimeImmutable $revokedAt,
        private ?RefreshSessionId $replacedByTokenId,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $lastUsedAt,
        private readonly ?string $userAgentHash,
        private readonly ?string $ipHash,
    ) {
    }

    public static function create(
        RefreshSessionId $id,
        AccountId $accountId,
        RefreshTokenHash $tokenHash,
        RefreshSessionFamilyId $familyId,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $createdAt,
        ?string $userAgentHash = null,
        ?string $ipHash = null,
    ): self {
        return new self(
            $id,
            $accountId,
            $tokenHash,
            $familyId,
            $expiresAt,
            null,
            null,
            $createdAt,
            null,
            self::normalizeNullableHash($userAgentHash, 'User agent hash'),
            self::normalizeNullableHash($ipHash, 'IP hash'),
        );
    }

    public static function reconstitute(
        RefreshSessionId $id,
        AccountId $accountId,
        RefreshTokenHash $tokenHash,
        RefreshSessionFamilyId $familyId,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $revokedAt,
        ?RefreshSessionId $replacedByTokenId,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $lastUsedAt,
        ?string $userAgentHash,
        ?string $ipHash,
    ): self {
        return new self(
            $id,
            $accountId,
            $tokenHash,
            $familyId,
            $expiresAt,
            $revokedAt,
            $replacedByTokenId,
            $createdAt,
            $lastUsedAt,
            self::normalizeNullableHash($userAgentHash, 'User agent hash'),
            self::normalizeNullableHash($ipHash, 'IP hash'),
        );
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        if (null !== $this->revokedAt) {
            return;
        }

        $this->revokedAt = $now;
    }

    public function replaceWith(RefreshSessionId $newSessionId, \DateTimeImmutable $now): void
    {
        if ($this->id->equals($newSessionId)) {
            throw new \InvalidArgumentException('Refresh session cannot be replaced by itself.');
        }

        $this->replacedByTokenId = $newSessionId;
        $this->revoke($now);
    }

    public function markUsed(\DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function wasReplaced(): bool
    {
        return null !== $this->replacedByTokenId;
    }

    public function isUsable(\DateTimeImmutable $now): bool
    {
        return !$this->isExpired($now) && !$this->isRevoked() && !$this->wasReplaced();
    }

    public function id(): RefreshSessionId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function tokenHash(): RefreshTokenHash
    {
        return $this->tokenHash;
    }

    public function familyId(): RefreshSessionFamilyId
    {
        return $this->familyId;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function replacedByTokenId(): ?RefreshSessionId
    {
        return $this->replacedByTokenId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function userAgentHash(): ?string
    {
        return $this->userAgentHash;
    }

    public function ipHash(): ?string
    {
        return $this->ipHash;
    }

    private static function normalizeNullableHash(?string $value, string $label): ?string
    {
        if (null === $value) {
            return null;
        }

        if ('' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('%s cannot be empty when provided.', $label));
        }

        return $value;
    }
}
