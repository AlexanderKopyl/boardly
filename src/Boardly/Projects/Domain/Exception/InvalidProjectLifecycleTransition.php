<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Exception;

use InvalidArgumentException;

final class InvalidProjectLifecycleTransition extends InvalidArgumentException
{
    public static function deletedProjectIsTerminal(): self
    {
        return new self('Deleted projects are terminal and cannot be modified.');
    }
}
