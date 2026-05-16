<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Exception;

use DomainException;

final class ProjectAlreadyArchived extends DomainException
{
    public static function create(): self
    {
        return new self('Project is already archived.');
    }
}
