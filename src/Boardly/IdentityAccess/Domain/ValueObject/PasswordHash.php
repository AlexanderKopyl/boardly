<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidPasswordHash;

final class PasswordHash
{
    private const array SUPPORTED_PREFIXES = [
        '$2y$',
        '$2a$',
        '$2b$',
        '$argon2id$',
    ];

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw InvalidPasswordHash::empty();
        }

        foreach (self::SUPPORTED_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return new self($value);
            }
        }

        throw InvalidPasswordHash::unsupportedFormat();
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
