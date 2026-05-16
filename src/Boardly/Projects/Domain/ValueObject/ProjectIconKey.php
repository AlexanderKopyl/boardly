<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\ValueObject;

use App\Boardly\Projects\Domain\Exception\InvalidProjectIconKey;

final class ProjectIconKey
{
    private const string DEFAULT = 'folder';
    private const string PATTERN = '/\A[a-z][a-z0-9_-]{0,63}\z/';

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function default(): self
    {
        return new self(self::DEFAULT);
    }

    public static function fromString(string $value): self
    {
        $trimmedValue = trim($value);

        if ('' === $trimmedValue) {
            throw InvalidProjectIconKey::empty();
        }

        if (1 !== preg_match(self::PATTERN, $trimmedValue)) {
            throw InvalidProjectIconKey::invalidFormat($trimmedValue);
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

    public function __toString(): string
    {
        return $this->value;
    }
}
