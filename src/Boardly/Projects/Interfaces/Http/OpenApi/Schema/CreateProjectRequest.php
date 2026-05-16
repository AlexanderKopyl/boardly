<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CreateProjectRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Website Redesign'),
        new OA\Property(
            property: 'iconKey',
            type: 'string',
            nullable: true,
            maxLength: 64,
            pattern: '^[a-z][a-z0-9_-]{0,63}$',
            example: 'folder',
        ),
    ],
    type: 'object',
)]
final class CreateProjectRequest
{
}
