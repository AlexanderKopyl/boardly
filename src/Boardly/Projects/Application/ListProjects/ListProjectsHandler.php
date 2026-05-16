<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ListProjects;

use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final readonly class ListProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
    ) {
    }

    public function __invoke(ListProjectsQuery $query): ListProjectsResult
    {
        $ownerId = AccountId::fromString($query->ownerAccountId());
        $projects = $this->projects->findByOwner($ownerId);

        $items = array_map(
            fn ($project) => new ProjectListItem(
                $project->id()->value(),
                $project->name()->value(),
                $project->iconKey()->value(),
                $project->status()->value(),
                $project->createdAt()->format(\DateTimeInterface::ATOM),
            ),
            $projects,
        );

        return new ListProjectsResult($items);
    }
}
