<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateProjectResponse',
    required: ['projectId', 'status'],
    properties: [
        new OA\Property(property: 'projectId', type: 'string', format: 'uuid', example: '7f88c85d-4ef6-4f5e-88f9-c8c4605d208c'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
    ],
    type: 'object',
)]
final class CreateProjectResponse
{
}
