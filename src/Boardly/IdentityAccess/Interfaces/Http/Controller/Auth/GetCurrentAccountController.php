<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountQuery;
use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountResult;
use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser;
use App\Shared\Application\Bus\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class GetCurrentAccountController
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'], format: 'json')]
    #[OA\Get(
        path: '/api/auth/me',
        operationId: 'getCurrentAccount',
        summary: 'Get the currently authenticated account',
        description: 'Returns the safe identity bootstrap data for the authenticated account identified by the bearer access token.',
        tags: ['Auth'],
        security: [
            ['bearerAuth' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current authenticated account.',
                content: new OA\JsonContent(ref: '#/components/schemas/CurrentAccountResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing, invalid, expired, revoked, missing-account, or non-active bearer token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof AuthenticatedAccountUser) {
            throw new \LogicException(sprintf(
                'Expected authenticated user of type %s.',
                AuthenticatedAccountUser::class,
            ));
        }

        $result = $this->queryBus->ask(new GetCurrentAccountQuery($user->accountId()));
        if (!$result instanceof GetCurrentAccountResult) {
            throw new \LogicException(sprintf(
                'Expected %s from get current account query bus ask.',
                GetCurrentAccountResult::class,
            ));
        }

        return new JsonResponse([
            'id' => $result->id(),
            'email' => $result->email(),
            'name' => $result->name(),
            'status' => $result->status(),
        ]);
    }
}
