<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    required: ['accessToken', 'tokenType', 'expiresIn', 'account'],
    properties: [
        new OA\Property(property: 'accessToken', type: 'string', example: 'eyJhbGciOiJSUzI1NiJ9...'),
        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expiresIn', type: 'integer', example: 900),
        new OA\Property(property: 'account', ref: '#/components/schemas/LoginAccountData'),
    ],
    type: 'object',
)]
final class LoginResponse {}
