<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Security;

use App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class TestProtectedApiController
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof AuthenticatedAccountUser) {
            return new JsonResponse(['ok' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'ok' => true,
            'account' => [
                'id' => $user->accountId()->value(),
                'email' => $user->email(),
                'name' => $user->name(),
                'status' => $user->status(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
