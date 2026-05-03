<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

interface CommandBusInterface
{
    /**
     * @param object $command
     */
    public function dispatch(object $command): mixed;
}
