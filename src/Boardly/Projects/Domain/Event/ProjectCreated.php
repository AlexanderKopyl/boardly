<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Event;

use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final readonly class ProjectCreated implements DomainEvent
{
    public function __construct(
        private ProjectId $projectId,
        private AccountId $ownerAccountId,
        private ProjectName $name,
        private ProjectIconKey $iconKey,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function ownerAccountId(): AccountId
    {
        return $this->ownerAccountId;
    }

    public function name(): ProjectName
    {
        return $this->name;
    }

    public function iconKey(): ProjectIconKey
    {
        return $this->iconKey;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
