<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['email', 'plainPassword', 'name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'plainPassword', type: 'string', format: 'password', minLength: 8, maxLength: 4096, example: 'Password123!'),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'User Name'),
    ],
    type: 'object',
)]
final class RegisterRequest {}
