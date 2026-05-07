<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

final class RefreshSessionFamilyId
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
            throw new \InvalidArgumentException('Refresh session family id cannot be empty.');
        }

        if (1 !== preg_match(self::UUID_PATTERN, $value)) {
            throw new \InvalidArgumentException(sprintf('Refresh session family id "%s" is not a valid UUID.', $value));
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
