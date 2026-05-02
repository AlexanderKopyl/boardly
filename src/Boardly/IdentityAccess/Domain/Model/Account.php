<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Event\AccountRejected;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyActive;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyRejected;
use App\Boardly\IdentityAccess\Domain\Exception\AccountNotPendingApproval;
use App\Boardly\IdentityAccess\Domain\Result\AccountApprovalResult;
use App\Boardly\IdentityAccess\Domain\Result\AccountRegistrationResult;
use App\Boardly\IdentityAccess\Domain\Result\AccountRejectionResult;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class Account
{
    private function __construct(
        private readonly AccountId $id,
        private readonly Email $email,
        private readonly PasswordHash $passwordHash,
        private readonly AccountName $name,
        private AccountStatus $status,
        private readonly bool $isSystemAdmin,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?\DateTimeImmutable $approvedAt,
        private ?\DateTimeImmutable $rejectedAt,
        private ?\DateTimeImmutable $disabledAt,
    ) {
    }

    public static function register(
        AccountId $id,
        Email $email,
        PasswordHash $passwordHash,
        AccountName $name,
        \DateTimeImmutable $createdAt,
    ): AccountRegistrationResult {
        $account = new self(
            $id,
            $email,
            $passwordHash,
            $name,
            AccountStatus::pendingApproval(),
            false,
            $createdAt,
            $createdAt,
            null,
            null,
            null,
        );

        return new AccountRegistrationResult(
            $account,
            new AccountRegistered($id, $email, false, $createdAt),
        );
    }

    public static function createSystemAdmin(
        AccountId $id,
        Email $email,
        PasswordHash $passwordHash,
        AccountName $name,
        \DateTimeImmutable $createdAt,
    ): AccountRegistrationResult {
        $account = new self(
            $id,
            $email,
            $passwordHash,
            $name,
            AccountStatus::active(),
            true,
            $createdAt,
            $createdAt,
            $createdAt,
            null,
            null,
        );

        return new AccountRegistrationResult(
            $account,
            new AccountRegistered($id, $email, true, $createdAt),
        );
    }

    public function approve(\DateTimeImmutable $approvedAt): AccountApprovalResult
    {
        if ($this->status->isActive()) {
            throw AccountAlreadyActive::create();
        }

        if (!$this->status->isPendingApproval()) {
            throw AccountNotPendingApproval::create();
        }

        $this->status = AccountStatus::active();
        $this->updatedAt = $approvedAt;
        $this->approvedAt = $approvedAt;

        return new AccountApprovalResult(
            new AccountApproved($this->id, $approvedAt),
        );
    }

    public function reject(\DateTimeImmutable $rejectedAt): AccountRejectionResult
    {
        if ($this->status->isRejected()) {
            throw AccountAlreadyRejected::create();
        }

        if (!$this->status->isPendingApproval()) {
            throw AccountNotPendingApproval::create();
        }

        $this->status = AccountStatus::rejected();
        $this->updatedAt = $rejectedAt;
        $this->rejectedAt = $rejectedAt;

        return new AccountRejectionResult(
            new AccountRejected($this->id, $rejectedAt),
        );
    }

    public function id(): AccountId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function name(): AccountName
    {
        return $this->name;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    public function isSystemAdmin(): bool
    {
        return $this->isSystemAdmin;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function approvedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function rejectedAt(): ?\DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function disabledAt(): ?\DateTimeImmutable
    {
        return $this->disabledAt;
    }
}
