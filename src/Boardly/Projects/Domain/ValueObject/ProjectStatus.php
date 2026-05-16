<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectStatus;

final class ProjectStatus
{
    private const string ACTIVE = 'active';
    private const string ARCHIVED = 'archived';

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function archived(): self
    {
        return new self(self::ARCHIVED);
    }

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw InvalidProjectStatus::empty();
        }

        return match ($value) {
            self::ACTIVE => self::active(),
            self::ARCHIVED => self::archived(),
            default => throw InvalidProjectStatus::unknown($value),
        };
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this->value;
    }

    public function isArchived(): bool
    {
        return self::ARCHIVED === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
