<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    /**
     * @throws AccountNotFound
     */
    public function get(AccountId $id): Account;

    public function find(AccountId $id): ?Account;

    public function findByEmail(Email $email): ?Account;

    public function existsByEmail(Email $email): bool;

    public function countActiveSystemAdmins(): int;
}
