<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

interface AccessTokenIssuerInterface
{
    public function issueForAccount(AccountId $accountId, \DateTimeImmutable $issuedAt, ?int $ttlSeconds = null): AccessToken;
}
