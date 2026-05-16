<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'projects', schema: 'projects')]
class ProjectEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'owner_account_id', type: 'string', length: 36)]
    private string $ownerAccountId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(name: 'icon_key', type: 'string', length: 64, options: ['default' => 'folder'])]
    private string $iconKey = 'folder';

    #[ORM\Column(type: 'string')]
    private string $status;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'archived_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedAt;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    private int $version = 1;

    public function __construct(
        string $id,
        string $ownerAccountId,
        string $name,
        string $iconKey,
        string $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $archivedAt,
    ) {
        $this->id = $id;
        $this->ownerAccountId = $ownerAccountId;
        $this->name = $name;
        $this->iconKey = $iconKey;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->archivedAt = $archivedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerAccountId(): string
    {
        return $this->ownerAccountId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIconKey(): string
    {
        return $this->iconKey;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function updateMutableFields(
        string $name,
        string $iconKey,
        string $status,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $archivedAt,
    ): void {
        $this->name = $name;
        $this->iconKey = $iconKey;
        $this->status = $status;
        $this->updatedAt = $updatedAt;
        $this->archivedAt = $archivedAt;
    }
}
