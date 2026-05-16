<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Exception;

use RuntimeException;

final class ProjectMappingFailed extends RuntimeException
{
    public static function forMismatchedProjectId(string $domainId, string $entityId): self
    {
        return new self(sprintf('Cannot update ProjectEntity with ID "%s" using Project aggregate with ID "%s".', $entityId, $domainId));
    }
}
