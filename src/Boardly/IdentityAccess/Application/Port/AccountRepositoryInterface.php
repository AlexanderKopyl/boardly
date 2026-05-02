<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(AccountId $id): ?Account;

    public function findByEmail(Email $email): ?Account;
}
