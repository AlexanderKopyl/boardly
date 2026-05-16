<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Application\ListProjects;

use App\Boardly\Projects\Application\ListProjects\ListProjectsHandler;
use App\Boardly\Projects\Application\ListProjects\ListProjectsQuery;
use App\Boardly\Projects\Application\ListProjects\ListProjectsResult;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class ListProjectsHandlerTest extends TestCase
{
    private const string OWNER_ID = '550e8400-e29b-41d4-a716-446655440001';

    public function test_it_lists_projects_for_owner(): void
    {
        $ownerId = AccountId::fromString(self::OWNER_ID);
        $project1 = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            $ownerId,
            ProjectName::fromString('Project 1'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new \DateTimeImmutable('2026-05-16T10:00:00+00:00'),
            new \DateTimeImmutable('2026-05-16T10:00:00+00:00'),
            null,
            null
        );
        $project2 = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440002'),
            $ownerId,
            ProjectName::fromString('Project 2'),
            ProjectIconKey::fromString('folder'),
            ProjectStatus::active(),
            new \DateTimeImmutable('2026-05-16T11:00:00+00:00'),
            new \DateTimeImmutable('2026-05-16T11:00:00+00:00'),
            null,
            null
        );

        $repository = new FakeProjectRepository([$project1, $project2]);
        $handler = new ListProjectsHandler($repository);

        $result = $handler->__invoke(new ListProjectsQuery(self::OWNER_ID));

        $this->assertInstanceOf(ListProjectsResult::class, $result);
        $this->assertCount(2, $result->projects());
        $this->assertSame('Project 1', $result->projects()[0]->name);
        $this->assertSame('Project 2', $result->projects()[1]->name);
    }
}

final class FakeProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(private array $projects = []) {}
    public function save(Project $project): void {}
    public function get(ProjectId $id): Project { throw new \LogicException('Not implemented'); }
    public function find(ProjectId $id): ?Project { return null; }
    public function findByOwner(AccountId $ownerId): array { return $this->projects; }
}
