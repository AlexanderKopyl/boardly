<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Domain\Model;

use App\Boardly\Projects\Domain\Event\ProjectArchived;
use App\Boardly\Projects\Domain\Event\ProjectCreated;
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
            null
        );

        $now = new \DateTimeImmutable();
        $result = $project->archive($now);

        $this->assertTrue($project->status()->isArchived());
        $this->assertSame($now, $project->updatedAt());
        $this->assertSame($now, $project->archivedAt());

        $event = $result->event();
        $this->assertInstanceOf(ProjectArchived::class, $event);
        $this->assertTrue($event->projectId()->equals($project->id()));
        $this->assertSame($now, $event->archivedAt());
    }
}
