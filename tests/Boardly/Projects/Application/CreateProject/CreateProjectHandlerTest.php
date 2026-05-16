<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Application\CreateProject;

use App\Boardly\Projects\Application\CreateProject\CreateProjectCommand;
use App\Boardly\Projects\Application\CreateProject\CreateProjectHandler;
use App\Boardly\Projects\Application\CreateProject\CreateProjectResult;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Event\ProjectCreated;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateProjectHandlerTest extends TestCase
{
    private const string OWNER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string PROJECT_ID = '550e8400-e29b-41d4-a716-446655440000';

    public function test_it_creates_a_project(): void
    {
        $repository = new FakeProjectRepository();
        $outbox = new FakeOutbox();
        $now = new DateTimeImmutable();

        $handler = new CreateProjectHandler(
            $repository,
            new FakeClock($now),
            new FakeIdGenerator(self::PROJECT_ID),
            new FakeTransactional(),
            $outbox
        );

        $result = $handler->__invoke(new CreateProjectCommand(
            self::OWNER_ID,
            'Test Project',
            'rocket'
        ));

        $this->assertInstanceOf(CreateProjectResult::class, $result);
        $this->assertSame(self::PROJECT_ID, $result->projectId());
        $this->assertSame('active', $result->status());

        $this->assertCount(1, $repository->savedProjects);
        $project = $repository->savedProjects[0];
        $this->assertSame('Test Project', $project->name()->value());
        $this->assertSame('rocket', $project->iconKey()->value());
        $this->assertSame(self::OWNER_ID, $project->ownerAccountId()->value());

        $this->assertCount(1, $outbox->storedEvents);
        $this->assertInstanceOf(ProjectCreated::class, $outbox->storedEvents[0]);
    }

    public function test_it_uses_default_icon_if_none_provided(): void
    {
        $repository = new FakeProjectRepository();
        $outbox = new FakeOutbox();
        $now = new DateTimeImmutable();

        $handler = new CreateProjectHandler(
            $repository,
            new FakeClock($now),
            new FakeIdGenerator(self::PROJECT_ID),
            new FakeTransactional(),
            $outbox
        );

        $handler->__invoke(new CreateProjectCommand(
            self::OWNER_ID,
            'Test Project'
        ));

        $project = $repository->savedProjects[0];
        $this->assertSame('folder', $project->iconKey()->value());
    }
}

final class FakeProjectRepository implements ProjectRepositoryInterface
{
    /** @var Project[] */
    public array $savedProjects = [];

    public function save(Project $project): void
    {
        $this->savedProjects[] = $project;
    }

    public function get(ProjectId $id): Project
    {
        throw new \LogicException('Not implemented');
    }

    public function find(ProjectId $id): ?Project
    {
        return null;
    }

    public function findByOwner(AccountId $ownerId): array
    {
        return [];
    }
}

final class FakeClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now) {}
    public function now(): DateTimeImmutable { return $this->now; }
}

final class FakeIdGenerator implements IdGeneratorInterface
{
    public function __construct(private string $id) {}
    public function generate(): string { return $this->id; }
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
