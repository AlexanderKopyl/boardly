<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountCommand;
use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountResult;
use App\Boardly\IdentityAccess\Interfaces\Http\Request\RegisterAccountRequestDto;
use App\Shared\Application\Bus\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RegisterAccountController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'registerAccount',
        summary: 'Register a new account',
        description: 'Creates a new account in pending_approval status. Does not issue access tokens or set cookies.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Account registered successfully.',
                content: new OA\JsonContent(ref: '#/components/schemas/RegisterResponse'),
            ),
            new OA\Response(
                response: 400,
                description: 'Malformed or unreadable request body.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 409,
                description: 'Email is already registered.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 422,
                description: 'Request payload is invalid (validation failed).',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
            ),
        ],
    )]
    public function __invoke(
        #[MapRequestPayload] RegisterAccountRequestDto $requestDto,
    ): JsonResponse {
        $result = $this->commandBus->dispatch(new RegisterAccountCommand(
            email: $requestDto->email,
            plainPassword: $requestDto->plainPassword,
            name: $requestDto->name,
        ));
        if (!$result instanceof RegisterAccountResult) {
            throw new \LogicException(sprintf(
                'Expected %s from register account command bus dispatch.',
                RegisterAccountResult::class
            ));
        }

        return new JsonResponse(
            [
                'accountId' => $result->accountId(),
                'status' => $result->status(),
            ],
            JsonResponse::HTTP_CREATED,
        );
    }
}
