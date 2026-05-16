<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Application\DeleteProject;

use App\Boardly\Projects\Application\DeleteProject\DeleteProjectCommand;
use App\Boardly\Projects\Application\DeleteProject\DeleteProjectHandler;
use App\Boardly\Projects\Application\DeleteProject\DeleteProjectResult;
use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Event\ProjectDeleted;
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

final class DeleteProjectHandlerTest extends TestCase
{
    private const string OWNER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string PROJECT_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const string OTHER_ACCOUNT_ID = '550e8400-e29b-41d4-a716-446655440003';

    public function test_it_deletes_a_project(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('-1 day'),
            null,
            null
        );

        $repository = new FakeProjectRepository($project);
        $outbox = new FakeOutbox();
        $now = new DateTimeImmutable();

        $handler = new DeleteProjectHandler(
            $repository,
            new FakeClock($now),
            new FakeTransactional(),
            $outbox
        );

        $result = $handler->__invoke(new DeleteProjectCommand(self::PROJECT_ID, self::OWNER_ID));

        $this->assertInstanceOf(DeleteProjectResult::class, $result);
        $this->assertSame(self::PROJECT_ID, $result->projectId());
        $this->assertSame('deleted', $result->status());

        $this->assertCount(1, $repository->savedProjects);
        $savedProject = $repository->savedProjects[0];
        $this->assertTrue($savedProject->status()->isDeleted());
        $this->assertSame($now, $savedProject->deletedAt());

        $this->assertCount(1, $outbox->storedEvents);
        $this->assertInstanceOf(ProjectDeleted::class, $outbox->storedEvents[0]);
    }

    public function test_it_throws_exception_if_project_not_found(): void
    {
        $repository = new FakeProjectRepository(null);
        $handler = new DeleteProjectHandler(
            $repository,
            new FakeClock(new DateTimeImmutable()),
            new FakeTransactional(),
            new FakeOutbox()
        );

        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new DeleteProjectCommand(self::PROJECT_ID, self::OWNER_ID));
    }

    public function test_it_throws_exception_if_not_owner(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('-1 day'),
            null,
            null
        );

        $repository = new FakeProjectRepository($project);
        $handler = new DeleteProjectHandler(
            $repository,
            new FakeClock(new DateTimeImmutable()),
            new FakeTransactional(),
            new FakeOutbox()
        );

        // ADR-0008: Return 404 for unauthorized access
        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new DeleteProjectCommand(self::PROJECT_ID, self::OTHER_ACCOUNT_ID));
    }

    public function test_it_throws_exception_if_project_is_deleted(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Deleted Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::deleted(),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('-1 day'),
            null,
            new DateTimeImmutable('-1 hour')
        );

        $repository = new FakeProjectRepository($project);
        $handler = new DeleteProjectHandler(
            $repository,
            new FakeClock(new DateTimeImmutable()),
            new FakeTransactional(),
            new FakeOutbox()
        );

        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new DeleteProjectCommand(self::PROJECT_ID, self::OWNER_ID));
    }
}

final class FakeProjectRepository implements ProjectRepositoryInterface
{
    /** @var Project[] */
    public array $savedProjects = [];

    public function __construct(private ?Project $project = null) {}

    public function save(Project $project): void
    {
        $this->savedProjects[] = $project;
    }

    public function getAccessibleById(ProjectId $id, AccountId $currentAccountId): Project
    {
        if (!$this->project instanceof Project) {
            throw ProjectNotFound::withId($id);
        }

        if (!$this->project->ownerAccountId()->equals($currentAccountId) || $this->project->status()->isDeleted()) {
            throw ProjectNotFound::withId($id);
        }

        return $this->project;
    }

    public function findAccessibleActiveByOwner(AccountId $ownerId): array
    {
        return [];
    }
}

final class FakeClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeTransactional implements TransactionalInterface
{
    public function transactional(callable $operation): mixed
    {
        return $operation();
    }
}

final class FakeOutbox implements OutboxInterface
{
    /** @var DomainEvent[] */
    public array $storedEvents = [];

    public function store(array $events): void
    {
        $this->storedEvents = array_merge($this->storedEvents, $events);
    }
}
