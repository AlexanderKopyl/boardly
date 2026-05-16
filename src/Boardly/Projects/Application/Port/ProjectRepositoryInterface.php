<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\Port;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    /**
     * @throws ProjectNotFound
     */
    public function get(ProjectId $id): Project;

    public function find(ProjectId $id): ?Project;

    /**
     * @return Project[]
     */
    public function findByOwner(AccountId $ownerId): array;
}
