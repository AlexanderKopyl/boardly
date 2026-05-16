<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectStatus;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use PHPUnit\Framework\TestCase;

final class ProjectStatusTest extends TestCase
{
    public function test_it_can_be_active(): void
    {
        $status = ProjectStatus::active();
        $this->assertSame('active', $status->value());
        $this->assertTrue($status->isActive());
        $this->assertFalse($status->isArchived());
    }

    public function test_it_can_be_archived(): void
    {
        $status = ProjectStatus::archived();
        $this->assertSame('archived', $status->value());
        $this->assertTrue($status->isArchived());
        $this->assertFalse($status->isActive());
    }

    public function test_it_can_be_created_from_string(): void
    {
        $this->assertTrue(ProjectStatus::fromString('active')->isActive());
        $this->assertTrue(ProjectStatus::fromString('archived')->isArchived());
    }

    public function test_it_throws_exception_for_unknown_status(): void
    {
        $this->expectException(InvalidProjectStatus::class);
        $this->expectExceptionMessage('Unknown ProjectStatus: "unknown".');

        ProjectStatus::fromString('unknown');
    }

    public function test_it_can_be_compared(): void
    {
        $s1 = ProjectStatus::active();
        $s2 = ProjectStatus::active();
        $s3 = ProjectStatus::archived();

        $this->assertTrue($s1->equals($s2));
        $this->assertFalse($s1->equals($s3));
    }
}
