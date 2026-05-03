<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountCommand;
use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountHandler;
use App\Boardly\IdentityAccess\Interfaces\Http\Request\RegisterAccountRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RegisterAccountController
{
    public function __construct(
        private RegisterAccountHandler $handler,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload] RegisterAccountRequestDto $requestDto,
    ): JsonResponse {
        $result = ($this->handler)(new RegisterAccountCommand(
            email: $requestDto->email,
            plainPassword: $requestDto->plainPassword,
            name: $requestDto->name,
        ));

        return new JsonResponse(
            [
                'accountId' => $result->accountId(),
                'status' => $result->status(),
            ],
            JsonResponse::HTTP_CREATED,
        );
    }
}
