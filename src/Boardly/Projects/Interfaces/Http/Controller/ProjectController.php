<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Controller;

use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser;
use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectCommand;
use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectResult;
use App\Boardly\Projects\Application\CreateProject\CreateProjectCommand;
use App\Boardly\Projects\Application\CreateProject\CreateProjectResult;
use App\Boardly\Projects\Application\DeleteProject\DeleteProjectCommand;
use App\Boardly\Projects\Application\DeleteProject\DeleteProjectResult;
use App\Boardly\Projects\Application\GetProject\GetProjectQuery;
use App\Boardly\Projects\Application\GetProject\GetProjectResult;
use App\Boardly\Projects\Application\ListProjects\ListProjectsQuery;
use App\Boardly\Projects\Application\ListProjects\ListProjectsResult;
use App\Boardly\Projects\Interfaces\Http\Request\CreateProjectRequestDto;
use App\Boardly\Projects\Interfaces\Http\Response\CreateProjectResponseDto;
use App\Boardly\Projects\Interfaces\Http\Response\GetProjectResponseDto;
use App\Boardly\Projects\Interfaces\Http\Response\ListProjectsResponseDto;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class ProjectController
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/api/projects', name: 'api_projects_create', methods: ['POST'], format: 'json')]
    #[OA\Post(
        path: '/api/projects',
        operationId: 'createProject',
        description: 'Creates a new project owned by the authenticated account. The icon key is optional and defaults to folder when omitted.',
        summary: 'Create a project',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateProjectRequest'),
        ),
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created.',
                content: new OA\JsonContent(ref: '#/components/schemas/CreateProjectResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 422,
                description: 'Request payload is invalid (validation failed).',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
            ),
        ],
    )]
    public function create(
        #[MapRequestPayload] CreateProjectRequestDto $requestDto,
    ): JsonResponse {
        $result = $this->commandBus->dispatch(
            new CreateProjectCommand(
                ownerAccountId: $this->currentAccountId(),
                name: $requestDto->name,
                iconKey: $requestDto->iconKey,
            )
        );

        if (!$result instanceof CreateProjectResult) {
            throw new \LogicException(sprintf('Expected %s from create project handler.', CreateProjectResult::class));
        }

        return new JsonResponse(
            CreateProjectResponseDto::fromResult($result)->toArray(),
            JsonResponse::HTTP_CREATED,
        );
    }

    #[Route('/api/projects', name: 'api_projects_list', methods: ['GET'], format: 'json')]
    #[OA\Get(
        path: '/api/projects',
        operationId: 'listProjects',
        description: 'Returns the projects owned by the authenticated account.',
        summary: 'List projects',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project list.',
                content: new OA\JsonContent(ref: '#/components/schemas/ListProjectsResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function list(): JsonResponse
    {
        $result = $this->queryBus->ask(new ListProjectsQuery($this->currentAccountId()));

        if (!$result instanceof ListProjectsResult) {
            throw new \LogicException(sprintf('Expected %s from list projects handler.', ListProjectsResult::class));
        }

        return new JsonResponse(
            ListProjectsResponseDto::fromResult($result)->toArray(),
        );
    }

    #[Route('/api/projects/{projectId}', name: 'api_projects_get', methods: ['GET'], format: 'json')]
    #[OA\Get(
        path: '/api/projects/{projectId}',
        operationId: 'getProject',
        description: 'Returns a project owned by the authenticated account. Missing or inaccessible projects return 404.',
        summary: 'Get a project',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'projectId',
                description: 'Project identifier.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project details.',
                content: new OA\JsonContent(ref: '#/components/schemas/GetProjectResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found or not accessible by the authenticated account.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function get(string $projectId): JsonResponse
    {
        $result = $this->queryBus->ask(
            new GetProjectQuery($projectId, $this->currentAccountId())
        );

        if (!$result instanceof GetProjectResult) {
            throw new \LogicException(sprintf('Expected %s from get project handler.', GetProjectResult::class));
        }

        return new JsonResponse(
            GetProjectResponseDto::fromResult($result)->toArray(),
        );
    }

    #[Route('/api/projects/{projectId}/archive', name: 'api_projects_archive', methods: ['POST'], format: 'json')]
    #[OA\Post(
        path: '/api/projects/{projectId}/archive',
        operationId: 'archiveProject',
        description: 'Archives a project owned by the authenticated account. Missing or inaccessible projects return 404.',
        summary: 'Archive a project',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'projectId',
                description: 'Project identifier.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Project archived.',
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found or not accessible by the authenticated account.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function archive(string $projectId): Response
    {
        $result = $this->commandBus->dispatch(
            new ArchiveProjectCommand($projectId, $this->currentAccountId())
        );

        if (!$result instanceof ArchiveProjectResult) {
            throw new \LogicException(sprintf('Expected %s from archive project handler.', ArchiveProjectResult::class));
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/projects/{projectId}', name: 'api_projects_delete', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        path: '/api/projects/{projectId}',
        operationId: 'deleteProject',
        description: 'Deletes a project owned by the authenticated account. Missing or inaccessible projects return 404.',
        summary: 'Delete a project',
        security: [
            ['bearerAuth' => []],
        ],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'projectId',
                description: 'Project identifier.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Project deleted.',
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found or not accessible by the authenticated account.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function delete(string $projectId): Response
    {
        $result = $this->commandBus->dispatch(
            new DeleteProjectCommand($projectId, $this->currentAccountId())
        );

        if (!$result instanceof DeleteProjectResult) {
            throw new \LogicException(sprintf('Expected %s from delete project handler.', DeleteProjectResult::class));
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function currentAccountId(): string
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof AuthenticatedAccountUser) {
            throw new \LogicException(sprintf('Expected authenticated user of type %s.', AuthenticatedAccountUser::class));
        }

        return $user->accountId()->value();
    }
}
