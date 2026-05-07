<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;

final readonly class HmacRefreshTokenHasher implements RefreshTokenHasherInterface
{
    public function __construct(
        #[\SensitiveParameter]
        private string $secret,
    ) {
        if ('' === trim($this->secret)) {
            throw new \InvalidArgumentException('Refresh token hash secret cannot be empty.');
        }
    }

    public function hash(string $rawRefreshToken): RefreshTokenHash
    {
        if ('' === trim($rawRefreshToken)) {
            throw new \InvalidArgumentException('Refresh token cannot be empty.');
        }

        return RefreshTokenHash::fromString('hmac-sha256:'.hash_hmac('sha256', $rawRefreshToken, $this->secret));
    }
}
