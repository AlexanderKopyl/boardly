<?php

declare(strict_types=1);

namespace App\Shared\Application\Port;

interface IdGeneratorInterface
{
    public function generate(): string;
}
