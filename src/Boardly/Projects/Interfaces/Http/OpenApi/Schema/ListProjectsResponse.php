<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ListProjectsResponse',
    required: ['projects'],
    properties: [
        new OA\Property(
            property: 'projects',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProjectListItemResponse'),
        ),
    ],
    type: 'object',
)]
final class ListProjectsResponse
{
}
