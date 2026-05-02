<?php

declare(strict_types=1);

namespace App\Boardly\SharedKernel\Domain\ValueObject;

use App\Boardly\SharedKernel\Domain\Exception\InvalidAccountId;

final class AccountId
{
    private const string UUID_PATTERN = '/\A[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\z/';

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = strtolower($value);
    }

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw InvalidAccountId::empty();
        }

        if (1 !== preg_match(self::UUID_PATTERN, $value)) {
            throw InvalidAccountId::invalidUuid($value);
        }

        return new self($value);
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
