<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Application\GetProject;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\GetProject\GetProjectHandler;
use App\Boardly\Projects\Application\GetProject\GetProjectQuery;
use App\Boardly\Projects\Application\GetProject\GetProjectResult;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class GetProjectHandlerTest extends TestCase
{
    private const string OWNER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string PROJECT_ID = '550e8400-e29b-41d4-a716-446655440000';
    private const string OTHER_ACCOUNT_ID = '550e8400-e29b-41d4-a716-446655440003';

    public function test_it_gets_a_project(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            null
        );

        $repository = new FakeProjectRepository($project);
        $handler = new GetProjectHandler($repository);

        $result = $handler->__invoke(new GetProjectQuery(self::PROJECT_ID, self::OWNER_ID));

        $this->assertInstanceOf(GetProjectResult::class, $result);
        $this->assertSame(self::PROJECT_ID, $result->id());
        $this->assertSame('Test Project', $result->name());
    }

    public function test_it_throws_exception_if_project_not_found(): void
    {
        $repository = new FakeProjectRepository(null);
        $handler = new GetProjectHandler($repository);

        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new GetProjectQuery(self::PROJECT_ID, self::OWNER_ID));
    }

    public function test_it_throws_exception_if_not_owner(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            null
        );

        $repository = new FakeProjectRepository($project);
        $handler = new GetProjectHandler($repository);

        // ADR-0008: Return 404 for unauthorized access
        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new GetProjectQuery(self::PROJECT_ID, self::OTHER_ACCOUNT_ID));
    }

    public function test_it_throws_exception_if_project_is_deleted(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString(self::PROJECT_ID),
            AccountId::fromString(self::OWNER_ID),
            ProjectName::fromString('Deleted Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::deleted(),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            null,
            new \DateTimeImmutable(),
        );

        $repository = new FakeProjectRepository($project);
        $handler = new GetProjectHandler($repository);

        $this->expectException(ProjectNotFound::class);
        $handler->__invoke(new GetProjectQuery(self::PROJECT_ID, self::OWNER_ID));
    }
}

final class FakeProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(private ?Project $project = null) {}
    public function save(Project $project): void {}
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
