<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\GetProject;

use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {
    }

    public function __invoke(GetProjectQuery $query): GetProjectResult
    {
        $id = ProjectId::fromString($query->projectId());
        $currentAccountId = AccountId::fromString($query->currentAccountId());
        $project = $this->projects->getAccessibleById($id, $currentAccountId);

        return new GetProjectResult(
            $project->id()->value(),
            $project->name()->value(),
            $project->iconKey()->value(),
            $project->status()->value(),
            $project->createdAt()->format(\DateTimeInterface::ATOM),
            $project->updatedAt()->format(\DateTimeInterface::ATOM),
            $project->archivedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
