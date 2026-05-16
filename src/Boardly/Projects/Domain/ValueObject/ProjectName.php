<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectName;

final class ProjectName
{
    private const int MAX_LENGTH = 100;

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $trimmedValue = trim($value);

        if ('' === $trimmedValue) {
            throw InvalidProjectName::empty();
        }

        if (self::length($trimmedValue) > self::MAX_LENGTH) {
            throw InvalidProjectName::tooLong(self::MAX_LENGTH);
        }

        return new self($trimmedValue);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
