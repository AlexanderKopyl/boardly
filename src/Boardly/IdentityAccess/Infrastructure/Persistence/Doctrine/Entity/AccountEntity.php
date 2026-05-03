<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'uniq_accounts_email', columns: ['email'])]
class AccountEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string')]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'string')]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $status;

    #[ORM\Column(name: 'is_system_admin', type: 'boolean', options: ['default' => false])]
    private bool $isSystemAdmin;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'approved_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt;

    #[ORM\Column(name: 'rejected_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $rejectedAt;

    #[ORM\Column(name: 'disabled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $disabledAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(
        string $id,
        string $email,
        string $passwordHash,
        string $name,
        string $status,
        bool $isSystemAdmin,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $approvedAt,
        ?\DateTimeImmutable $rejectedAt,
        ?\DateTimeImmutable $disabledAt,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->name = $name;
        $this->status = $status;
        $this->isSystemAdmin = $isSystemAdmin;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->approvedAt = $approvedAt;
        $this->rejectedAt = $rejectedAt;
        $this->disabledAt = $disabledAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSystemAdmin(): bool
    {
        return $this->isSystemAdmin;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getRejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function getDisabledAt(): ?\DateTimeImmutable
    {
        return $this->disabledAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function updateMutableFields(
        string $email,
        string $passwordHash,
        string $name,
        string $status,
        bool $isSystemAdmin,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $approvedAt,
        ?\DateTimeImmutable $rejectedAt,
        ?\DateTimeImmutable $disabledAt,
    ): void {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->name = $name;
        $this->status = $status;
        $this->isSystemAdmin = $isSystemAdmin;
        $this->updatedAt = $updatedAt;
        $this->approvedAt = $approvedAt;
        $this->rejectedAt = $rejectedAt;
        $this->disabledAt = $disabledAt;
    }
}
