<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\OpenApi;

use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

/**
 * Registers standalone #[OA\Schema] classes from the Projects Schema/ directory
 * so controller $ref references can resolve them into components.schemas.
 */
final class SchemaDescriber implements DescriberInterface
{
    public function describe(OA\OpenApi $api): void
    {
        $schemaDir = __DIR__ . '/Schema';

        $scanned = (new Generator())->generate([$schemaDir], validate: false);

        if (null === $scanned) {
            return;
        }

        $components = $scanned->components;
        if (Generator::isDefault($components)) {
            return;
        }

        $schemas = $components->schemas;
        if (Generator::isDefault($schemas)) {
            return;
        }

        foreach ($schemas as $schema) {
            $encoded = json_encode($schema);
            if (false === $encoded) {
                continue;
            }
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($encoded, true);
            Util::merge($api, ['components' => ['schemas' => [$schema->schema => $decoded]]]);
        }
    }
}
