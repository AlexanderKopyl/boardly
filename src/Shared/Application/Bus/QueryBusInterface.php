<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

interface QueryBusInterface
{
    /**
     * @param object $query
     */
    public function ask(object $query): mixed;
}
