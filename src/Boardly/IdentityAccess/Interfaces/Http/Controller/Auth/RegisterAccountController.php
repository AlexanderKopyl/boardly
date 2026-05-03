<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountCommand;
use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountResult;
use App\Boardly\IdentityAccess\Interfaces\Http\Request\RegisterAccountRequestDto;
use App\Shared\Application\Bus\CommandBusInterface;
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
