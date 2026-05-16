<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\Exception;

use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final class ProjectNotFound extends ProjectApplicationException
{
    public static function withId(ProjectId $id): self
    {
        return self::withIdentifier($id->value());
    }

    public static function withIdentifier(string $projectId): self
    {
        return new self(sprintf('Project with ID "%s" not found.', $projectId));
    }
}
