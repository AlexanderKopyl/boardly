<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountQuery;
use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountResult;
use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser;
use App\Shared\Application\Bus\QueryBusInterface;
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
