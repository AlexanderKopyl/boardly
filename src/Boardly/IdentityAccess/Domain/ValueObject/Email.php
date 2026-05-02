<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;

final class Email
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $normalizedValue = strtolower(trim($value));

        if ('' === $normalizedValue) {
            throw InvalidEmail::empty();
        }

        if (false === filter_var($normalizedValue, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmail::invalidFormat();
        }

        return new self($normalizedValue);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
