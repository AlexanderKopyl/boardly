<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorEnvelope',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'email_already_registered'),
                new OA\Property(property: 'message', type: 'string', example: 'Email is already registered.'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
final class ErrorEnvelope {}
