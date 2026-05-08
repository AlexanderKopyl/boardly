<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationErrorEnvelope',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message', 'violations'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'validation_failed'),
                new OA\Property(property: 'message', type: 'string', example: 'The request payload is invalid.'),
                new OA\Property(
                    property: 'violations',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Violation'),
                ),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
final class ValidationErrorEnvelope {}
