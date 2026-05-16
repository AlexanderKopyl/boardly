<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Domain\Model;

use App\Boardly\Projects\Domain\Event\ProjectArchived;
use App\Boardly\Projects\Domain\Event\ProjectCreated;
use App\Boardly\Projects\Domain\Event\ProjectDeleted;
use App\Boardly\Projects\Domain\Exception\InvalidProjectLifecycleTransition;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    public function test_it_can_be_created(): void
    {
        $id = ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $ownerId = AccountId::fromString('550e8400-e29b-41d4-a716-446655440001');
        $name = ProjectName::fromString('Test Project');
        $iconKey = ProjectIconKey::fromString('rocket');
        $now = new \DateTimeImmutable();

        $result = Project::create($id, $ownerId, $name, $iconKey, $now);

        $project = $result->project();
        $this->assertTrue($project->id()->equals($id));
        $this->assertTrue($project->ownerAccountId()->equals($ownerId));
        $this->assertTrue($project->name()->equals($name));
        $this->assertTrue($project->iconKey()->equals($iconKey));
        $this->assertTrue($project->status()->isActive());
        $this->assertSame($now, $project->createdAt());
        $this->assertSame($now, $project->updatedAt());
        $this->assertNull($project->archivedAt());
        $this->assertNull($project->deletedAt());

        $event = $result->event();
        $this->assertInstanceOf(ProjectCreated::class, $event);
        $this->assertTrue($event->projectId()->equals($id));
        $this->assertTrue($event->ownerAccountId()->equals($ownerId));
        $this->assertTrue($event->name()->equals($name));
        $this->assertTrue($event->iconKey()->equals($iconKey));
        $this->assertSame($now, $event->createdAt());
    }

    public function test_it_can_be_archived(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('-1 day'),
            null,
            null
        );

        $now = new \DateTimeImmutable();
        $result = $project->archive($now);

        $this->assertTrue($project->status()->isArchived());
        $this->assertSame($now, $project->updatedAt());
        $this->assertSame($now, $project->archivedAt());
        $this->assertNull($project->deletedAt());

        $event = $result->event();
        $this->assertInstanceOf(ProjectArchived::class, $event);
        $this->assertTrue($event->projectId()->equals($project->id()));
        $this->assertSame($now, $event->archivedAt());
    }

    public function test_it_returns_success_for_repeated_archive_calls_without_emitting_a_second_event(): void
    {
        $archivedAt = new \DateTimeImmutable('-1 hour');
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::archived(),
            new \DateTimeImmutable('-1 day'),
            $archivedAt,
            $archivedAt,
            null
        );

        $result = $project->archive(new \DateTimeImmutable());

        $this->assertTrue($project->status()->isArchived());
        $this->assertSame($archivedAt, $project->archivedAt());
        $this->assertSame($archivedAt, $project->updatedAt());
        $this->assertNull($result->event());
    }

    public function test_it_can_be_deleted_from_an_active_project(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::active(),
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('-1 day'),
            null,
            null
        );

        $deletedAt = new \DateTimeImmutable();
        $result = $project->delete($deletedAt);

        $this->assertTrue($project->status()->isDeleted());
        $this->assertSame($deletedAt, $project->updatedAt());
        $this->assertSame($deletedAt, $project->deletedAt());
        $this->assertNull($project->archivedAt());

        $event = $result->event();
        $this->assertInstanceOf(ProjectDeleted::class, $event);
        $this->assertTrue($event->projectId()->equals($project->id()));
        $this->assertSame($deletedAt, $event->deletedAt());
    }

    public function test_it_can_be_deleted_from_an_archived_project(): void
    {
        $archivedAt = new \DateTimeImmutable('-1 hour');
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::archived(),
            new \DateTimeImmutable('-1 day'),
            $archivedAt,
            $archivedAt,
            null
        );

        $deletedAt = new \DateTimeImmutable();
        $result = $project->delete($deletedAt);

        $this->assertTrue($project->status()->isDeleted());
        $this->assertSame($deletedAt, $project->updatedAt());
        $this->assertSame($deletedAt, $project->deletedAt());
        $this->assertSame($archivedAt, $project->archivedAt());

        $event = $result->event();
        $this->assertInstanceOf(ProjectDeleted::class, $event);
        $this->assertTrue($event->projectId()->equals($project->id()));
        $this->assertSame($deletedAt, $event->deletedAt());
    }

    public function test_it_rejects_mutation_after_deletion(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::deleted(),
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('-1 hour'),
            null,
            new \DateTimeImmutable('-1 hour')
        );

        $this->expectException(InvalidProjectLifecycleTransition::class);

        $project->archive(new \DateTimeImmutable());
    }

    public function test_it_rejects_delete_after_deletion(): void
    {
        $project = Project::reconstitute(
            ProjectId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            AccountId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            ProjectName::fromString('Test Project'),
            ProjectIconKey::fromString('rocket'),
            ProjectStatus::deleted(),
            new \DateTimeImmutable('-1 day'),
            new \DateTimeImmutable('-1 hour'),
            null,
            new \DateTimeImmutable('-1 hour')
        );

        $this->expectException(InvalidProjectLifecycleTransition::class);

        $project->delete(new \DateTimeImmutable());
    }
}
