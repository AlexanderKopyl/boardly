<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use PHPUnit\Framework\TestCase;

final class ProjectIconKeyTest extends TestCase
{
    public function test_it_has_default_value(): void
    {
        $iconKey = ProjectIconKey::default();
        $this->assertSame('folder', $iconKey->value());
    }

    public function test_it_can_be_created_from_valid_string(): void
    {
        $this->assertSame('rocket', ProjectIconKey::fromString('rocket')->value());
        $this->assertSame('project-123', ProjectIconKey::fromString('project-123')->value());
        $this->assertSame('my_icon', ProjectIconKey::fromString('my_icon')->value());
    }

    public function test_it_throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidProjectIconKey::class);
        ProjectIconKey::fromString('123-rocket'); // Starts with number
    }

    public function test_it_throws_exception_for_uppercase(): void
    {
        $this->expectException(InvalidProjectIconKey::class);
        ProjectIconKey::fromString('Rocket');
    }

    public function test_it_throws_exception_for_too_long(): void
    {
        $this->expectException(InvalidProjectIconKey::class);
        ProjectIconKey::fromString(str_repeat('a', 65));
    }

    public function test_it_throws_exception_for_empty(): void
    {
        $this->expectException(InvalidProjectIconKey::class);
        ProjectIconKey::fromString('');
    }

    public function test_it_can_be_compared(): void
    {
        $i1 = ProjectIconKey::fromString('rocket');
        $i2 = ProjectIconKey::fromString('rocket');
        $i3 = ProjectIconKey::fromString('folder');

        $this->assertTrue($i1->equals($i2));
        $this->assertFalse($i1->equals($i3));
    }
}
