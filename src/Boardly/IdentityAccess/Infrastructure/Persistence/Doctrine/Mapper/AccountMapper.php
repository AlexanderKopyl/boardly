<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class AccountMapper
{
    public function toEntity(Account $account): AccountEntity
    {
        return new AccountEntity(
            $account->id()->value(),
            $account->email()->value(),
            $account->passwordHash()->value(),
            $account->name()->value(),
            $account->status()->value(),
            $account->isSystemAdmin(),
            $account->createdAt(),
            $account->updatedAt(),
            $account->approvedAt(),
            $account->rejectedAt(),
            $account->disabledAt(),
        );
    }

    public function updateEntity(Account $account, AccountEntity $entity): void
    {
        $entity->updateMutableFields(
            $account->email()->value(),
            $account->passwordHash()->value(),
            $account->name()->value(),
            $account->status()->value(),
            $account->isSystemAdmin(),
            $account->updatedAt(),
            $account->approvedAt(),
            $account->rejectedAt(),
            $account->disabledAt(),
        );
    }

    public function toDomain(AccountEntity $entity): Account
    {
        return Account::reconstitute(
            AccountId::fromString($entity->getId()),
            Email::fromString($entity->getEmail()),
            PasswordHash::fromString($entity->getPasswordHash()),
            AccountName::fromString($entity->getName()),
            AccountStatus::fromString($entity->getStatus()),
            $entity->isSystemAdmin(),
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
            $entity->getApprovedAt(),
            $entity->getRejectedAt(),
            $entity->getDisabledAt(),
        );
    }
}
