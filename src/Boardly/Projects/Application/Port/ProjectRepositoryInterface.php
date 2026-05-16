<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\Port;

use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    /**
     * @throws \App\Boardly\Projects\Application\Exception\ProjectNotFound
     */
    public function getAccessibleById(ProjectId $id, AccountId $currentAccountId): Project;

    /**
     * @return Project[]
     */
    public function findAccessibleActiveByOwner(AccountId $ownerId): array;
}
