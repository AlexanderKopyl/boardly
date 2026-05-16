<?php

declare(strict_types=1);

namespace App\Tests\Boardly\SharedKernel\Domain\ValueObject;

use App\Boardly\SharedKernel\Domain\Exception\InvalidProjectId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectIdTest extends TestCase
{
    public function test_it_can_be_created_from_string(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $projectId = ProjectId::fromString($uuid);

        $this->assertSame($uuid, $projectId->value());
    }

    public function test_it_is_case_insensitive_and_stored_as_lowercase(): void
    {
        $uuid = '550E8400-E29B-41D4-A716-446655440000';
        $projectId = ProjectId::fromString($uuid);

        $this->assertSame(strtolower($uuid), $projectId->value());
    }

    public function test_it_throws_exception_if_empty(): void
    {
        $this->expectException(InvalidProjectId::class);
        $this->expectExceptionMessage('ProjectId must not be empty.');

        ProjectId::fromString('');
    }

    public function test_it_throws_exception_if_invalid_uuid(): void
    {
        $invalidUuid = 'invalid-uuid';
        $this->expectException(InvalidProjectId::class);
        $this->expectExceptionMessage(sprintf('ProjectId must be a valid UUID. "%s" given.', $invalidUuid));

        ProjectId::fromString($invalidUuid);
    }

    public function test_it_can_be_compared(): void
    {
        $uuid1 = '550e8400-e29b-41d4-a716-446655440000';
        $uuid2 = '550e8400-e29b-41d4-a716-446655440001';

        $id1 = ProjectId::fromString($uuid1);
        $id2 = ProjectId::fromString($uuid1);
        $id3 = ProjectId::fromString($uuid2);

        $this->assertTrue($id1->equals($id2));
        $this->assertFalse($id1->equals($id3));
    }
}
