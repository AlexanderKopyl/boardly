<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Exception;

final class AccountMappingFailed extends \RuntimeException
{
    public static function forMismatchedAccountId(string $accountId, string $entityId): self
    {
        return new self(sprintf(
            'Cannot update AccountEntity "%s" from Account "%s".',
            $entityId,
            $accountId,
        ));
    }
}
