<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ArchiveProject;

use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeInterface;

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
                $id = ProjectId::fromString($command->projectId());
                $project = $this->projects->getAccessibleById(
                    $id,
                    AccountId::fromString($command->currentAccountId()),
                );

                $domainResult = $project->archive($this->clock->now());

                $this->projects->save($project);
                $this->outbox->store([$domainResult->event()]);

                return new ArchiveProjectResult(
                    $project->id()->value(),
                    $project->status()->value(),
                    $domainResult->event()->archivedAt()->format(DateTimeInterface::ATOM),
                );
            }
        );
    }
}
