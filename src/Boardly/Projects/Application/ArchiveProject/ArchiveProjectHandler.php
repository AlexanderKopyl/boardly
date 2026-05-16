<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ArchiveProject;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class ArchiveProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private ClockInterface $clock,
        private TransactionalInterface $transactional,
        private OutboxInterface $outbox,
    ) {
    }

    public function __invoke(ArchiveProjectCommand $command): ArchiveProjectResult
    {
        return $this->transactional->transactional(
            function () use ($command): ArchiveProjectResult {
                $id = $this->projectId($command->projectId());
                $project = $this->projects->getAccessibleById(
                    $id,
                    AccountId::fromString($command->currentAccountId()),
                );

                $domainResult = $project->archive($this->clock->now());
                $archivedAt = $project->archivedAt() ?? $this->clock->now();
                $event = $domainResult->event();

                if (null !== $event) {
                    $this->projects->save($project);
                    $this->outbox->store([$event]);
                    $archivedAt = $event->archivedAt();
                }

                return new ArchiveProjectResult(
                    $project->id()->value(),
                    $project->status()->value(),
                    $archivedAt->format(\DateTimeInterface::ATOM),
                );
            }
        );
    }

    private function projectId(string $projectId): ProjectId
    {
        try {
            return ProjectId::fromString($projectId);
        } catch (\InvalidArgumentException) {
            throw ProjectNotFound::withIdentifier($projectId);
        }
    }
}
