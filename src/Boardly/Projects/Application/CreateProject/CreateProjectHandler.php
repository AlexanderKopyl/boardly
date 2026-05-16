<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\CreateProject;

use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class CreateProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private ClockInterface $clock,
        private IdGeneratorInterface $idGenerator,
        private TransactionalInterface $transactional,
        private OutboxInterface $outbox,
    ) {
    }

    public function __invoke(CreateProjectCommand $command): CreateProjectResult
    {
        return $this->transactional->transactional(
            function () use ($command): CreateProjectResult {
                $ownerId = AccountId::fromString($command->ownerAccountId());
                $name = ProjectName::fromString($command->name());
                $iconKey = $command->iconKey() !== null
                    ? ProjectIconKey::fromString($command->iconKey())
                    : ProjectIconKey::default();

                $id = ProjectId::fromString($this->idGenerator->generate());
                $now = $this->clock->now();

                $domainResult = Project::create($id, $ownerId, $name, $iconKey, $now);
                $project = $domainResult->project();

                $this->projects->save($project);
                $this->outbox->store([$domainResult->event()]);

                return new CreateProjectResult(
                    $project->id()->value(),
                    $project->status()->value(),
                );
            }
        );
    }
}
