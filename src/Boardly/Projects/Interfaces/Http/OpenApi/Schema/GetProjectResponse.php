<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GetProjectResponse',
    required: ['id', 'name', 'iconKey', 'status', 'createdAt', 'updatedAt', 'archivedAt'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7f88c85d-4ef6-4f5e-88f9-c8c4605d208c'),
        new OA\Property(property: 'name', type: 'string', example: 'Website Redesign'),
        new OA\Property(property: 'iconKey', type: 'string', example: 'folder'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-05-16T08:00:00+00:00'),
        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2026-05-16T08:00:00+00:00'),
        new OA\Property(property: 'archivedAt', type: 'string', format: 'date-time', nullable: true, example: null),
    ],
    type: 'object',
)]
final class GetProjectResponse
{
}
