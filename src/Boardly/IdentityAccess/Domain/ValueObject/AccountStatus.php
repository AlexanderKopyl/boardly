<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountStatus;

final class AccountStatus
{
    private const string PENDING_APPROVAL = 'pending_approval';
    private const string ACTIVE = 'active';
    private const string REJECTED = 'rejected';
    private const string DISABLED = 'disabled';

    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function pendingApproval(): self
    {
        return new self(self::PENDING_APPROVAL);
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    public static function disabled(): self
    {
        return new self(self::DISABLED);
    }

    public static function fromString(string $value): self
    {
        if ('' === $value) {
            throw InvalidAccountStatus::empty();
        }

        return match ($value) {
            self::PENDING_APPROVAL => self::pendingApproval(),
            self::ACTIVE => self::active(),
            self::REJECTED => self::rejected(),
            self::DISABLED => self::disabled(),
            default => throw InvalidAccountStatus::unknown($value),
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

    public function isPendingApproval(): bool
    {
        return self::PENDING_APPROVAL === $this->value;
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this->value;
    }

    public function isRejected(): bool
    {
        return self::REJECTED === $this->value;
    }

    public function isDisabled(): bool
    {
        return self::DISABLED === $this->value;
    }
}
