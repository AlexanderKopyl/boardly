<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Application\ArchiveProject;

use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectCommand;
use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectHandler;
use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectResult;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Event\ProjectArchived;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ArchiveProjectHandlerTest extends TestCase
{
    private const string OWNER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string PROJECT_ID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_it_archives_a_project(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('-1 day'),
            null
        );

        $repository = new FakeProjectRepository($project);
        $outbox = new FakeOutbox();
        $now = new DateTimeImmutable();

        $handler = new ArchiveProjectHandler(
            $repository,
            new FakeClock($now),
            new FakeTransactional(),
            $outbox
        );

        $result = $handler->__invoke(new ArchiveProjectCommand(self::PROJECT_ID, self::OWNER_ID));

        $this->assertInstanceOf(ArchiveProjectResult::class, $result);
        $this->assertSame(self::PROJECT_ID, $result->projectId());
        $this->assertSame('archived', $result->status());

        $this->assertTrue($project->status()->isArchived());
        $this->assertSame($now, $project->archivedAt());

        $this->assertCount(1, $outbox->storedEvents);
        $this->assertInstanceOf(ProjectArchived::class, $outbox->storedEvents[0]);
    }
}

final class FakeProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(private ?Project $project = null) {}
    public function save(Project $project): void {}
    public function get(ProjectId $id): Project { throw new \LogicException('Not implemented'); }
    public function find(ProjectId $id): ?Project { return $this->project; }
    public function findByOwner(AccountId $ownerId): array { return []; }
}

final class FakeClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now) {}
    public function now(): DateTimeImmutable { return $this->now; }
}

final class FakeTransactional implements TransactionalInterface
{
    public function transactional(callable $operation): mixed { return $operation(); }
}

final class FakeOutbox implements OutboxInterface
{
    /** @var DomainEvent[] */
    public array $storedEvents = [];
    public function store(array $events): void { $this->storedEvents = array_merge($this->storedEvents, $events); }
}
