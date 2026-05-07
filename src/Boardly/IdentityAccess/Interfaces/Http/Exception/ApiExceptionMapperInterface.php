<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiExceptionMapperInterface
{
    public static function getDefaultPriority(): int;

    public function supports(\Throwable $exception): bool;

    public function map(\Throwable $exception, Request $request): Response;
}
