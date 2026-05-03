<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Identity;

use App\Shared\Application\Port\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class UuidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
