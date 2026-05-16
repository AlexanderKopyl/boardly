<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Interfaces\Http\Exception;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Interfaces\Http\Exception\ProjectNotFoundExceptionMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProjectNotFoundExceptionMapperTest extends TestCase
{
    public function testSupportsProjectNotFoundExceptions(): void
    {
        $mapper = new ProjectNotFoundExceptionMapper();

        self::assertTrue($mapper->supports(ProjectNotFound::withId('018f3f7a-9e4c-7b2d-9c52-000000009901')));
        self::assertFalse($mapper->supports(new \RuntimeException('Expected test exception.')));
    }

    public function testMapsProjectNotFoundTo404Envelope(): void
    {
        $mapper = new ProjectNotFoundExceptionMapper();

        $response = $mapper->map(
            ProjectNotFound::withId('018f3f7a-9e4c-7b2d-9c52-000000009902'),
            Request::create('/api/projects/018f3f7a-9e4c-7b2d-9c52-000000009902'),
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            '{"error":{"code":"project_not_found","message":"Project not found."}}',
            $response->getContent(),
        );
    }
}
