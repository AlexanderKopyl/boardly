<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_sessions')]
#[ORM\UniqueConstraint(name: 'uniq_refresh_sessions_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_refresh_sessions_account_id', columns: ['account_id'])]
#[ORM\Index(name: 'idx_refresh_sessions_family_id', columns: ['family_id'])]
#[ORM\Index(name: 'idx_refresh_sessions_expires_at', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_refresh_sessions_revoked_at', columns: ['revoked_at'])]
class RefreshSessionEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: AccountEntity::class)]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AccountEntity $account;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 255)]
    private string $tokenHash;

    #[ORM\Column(name: 'family_id', type: 'string', length: 36)]
    private string $familyId;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt;

    #[ORM\Column(name: 'replaced_by_token_id', type: 'string', length: 36, nullable: true)]
    private ?string $replacedByTokenId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_used_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt;

    #[ORM\Column(name: 'user_agent_hash', type: 'string', length: 255, nullable: true)]
    private ?string $userAgentHash;

    #[ORM\Column(name: 'ip_hash', type: 'string', length: 255, nullable: true)]
    private ?string $ipHash;

    public function __construct(
        string $id,
        AccountEntity $account,
        string $tokenHash,
        string $familyId,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $revokedAt,
        ?string $replacedByTokenId,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $lastUsedAt,
        ?string $userAgentHash,
        ?string $ipHash,
    ) {
        $this->id = $id;
        $this->account = $account;
        $this->tokenHash = $tokenHash;
        $this->familyId = $familyId;
        $this->expiresAt = $expiresAt;
        $this->revokedAt = $revokedAt;
        $this->replacedByTokenId = $replacedByTokenId;
        $this->createdAt = $createdAt;
        $this->lastUsedAt = $lastUsedAt;
        $this->userAgentHash = $userAgentHash;
        $this->ipHash = $ipHash;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAccountId(): string
    {
        return $this->account->getId();
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getFamilyId(): string
    {
        return $this->familyId;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getReplacedByTokenId(): ?string
    {
        return $this->replacedByTokenId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getUserAgentHash(): ?string
    {
        return $this->userAgentHash;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function updateMutableFields(
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
        ?\DateTimeImmutable $revokedAt,
        ?string $replacedByTokenId,
        ?\DateTimeImmutable $lastUsedAt,
        ?string $userAgentHash,
        ?string $ipHash,
    ): void {
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->revokedAt = $revokedAt;
        $this->replacedByTokenId = $replacedByTokenId;
        $this->lastUsedAt = $lastUsedAt;
        $this->userAgentHash = $userAgentHash;
        $this->ipHash = $ipHash;
    }

    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        if (null === $this->revokedAt) {
            $this->revokedAt = $revokedAt;
        }
    }
}
