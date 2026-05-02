<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidPasswordHash;

final class PasswordHash
{
    private const string BCRYPT_PATTERN = '/^\$2[ayb]\$(0[4-9]|[12][0-9]|3[01])\$[.\/A-Za-z0-9]{53}$/';
    private const string ARGON2ID_PATTERN = '/^\$argon2id\$v=19\$m=\d+,t=\d+,p=\d+\$[A-Za-z0-9+\/]{8,}\$[A-Za-z0-9+\/]{16,}$/';

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

        if (1 === preg_match(self::BCRYPT_PATTERN, $value) || 1 === preg_match(self::ARGON2ID_PATTERN, $value)) {
            return new self($value);
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
