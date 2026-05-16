<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use PHPUnit\Framework\TestCase;

final class ProjectNameTest extends TestCase
{
    public function test_it_can_be_created_from_string(): void
    {
        $name = 'My Awesome Project';
        $projectName = ProjectName::fromString($name);

        $this->assertSame($name, $projectName->value());
    }

    public function test_it_trims_whitespace(): void
    {
        $name = '  My Awesome Project  ';
        $projectName = ProjectName::fromString($name);

        $this->assertSame('My Awesome Project', $projectName->value());
    }

    public function test_it_throws_exception_if_empty(): void
    {
        $this->expectException(InvalidProjectName::class);
        $this->expectExceptionMessage('Project name must not be empty.');

        ProjectName::fromString('   ');
    }

    public function test_it_throws_exception_if_too_long(): void
    {
        $longName = str_repeat('a', 101);
        $this->expectException(InvalidProjectName::class);
        $this->expectExceptionMessage('Project name must not exceed 100 characters.');

        ProjectName::fromString($longName);
    }

    public function test_it_can_be_compared(): void
    {
        $name1 = 'Project 1';
        $name2 = 'Project 2';

        $pn1 = ProjectName::fromString($name1);
        $pn2 = ProjectName::fromString($name1);
        $pn3 = ProjectName::fromString($name2);

        $this->assertTrue($pn1->equals($pn2));
        $this->assertFalse($pn1->equals($pn3));
    }
}
