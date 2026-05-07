<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

final class RefreshTokenHash
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('Refresh token hash cannot be empty.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
