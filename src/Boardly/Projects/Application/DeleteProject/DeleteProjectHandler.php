<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\DeleteProject;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class DeleteProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
        private OutboxInterface $outbox,
    ) {
    }

    public function __invoke(DeleteProjectCommand $command): DeleteProjectResult
    {
        return $this->transactional->transactional(
            function () use ($command): DeleteProjectResult {
                $id = ProjectId::fromString($command->projectId());
                $project = $this->projects->find($id);

                if ($project === null) {
                    throw ProjectNotFound::withId($id);
                }

                $currentAccountId = AccountId::fromString($command->currentAccountId());
                if (!$project->ownerAccountId()->equals($currentAccountId)) {
                    // ADR-0008: Return 404 instead of 403
                    throw ProjectNotFound::withId($id);
                }

                $domainResult = $project->delete($this->clock->now());

                $this->projects->save($project);
                $this->outbox->store([$domainResult->event()]);

                return new DeleteProjectResult(
                    $project->id()->value(),
                    $project->status()->value(),
                    $domainResult->event()->deletedAt()->format(\DateTimeInterface::ATOM),
                );
            }
        );
    }
}
