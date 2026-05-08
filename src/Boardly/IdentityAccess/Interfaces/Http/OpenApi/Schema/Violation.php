<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Violation',
    required: ['field', 'message'],
    properties: [
        new OA\Property(property: 'field', type: 'string', example: 'email'),
        new OA\Property(property: 'message', type: 'string', example: 'This value is not a valid email address.'),
    ],
    type: 'object',
)]
final class Violation {}
