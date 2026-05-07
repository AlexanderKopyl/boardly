<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticateAccountCommand;
use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticateAccountResult;
use App\Boardly\IdentityAccess\Interfaces\Http\Request\LoginRequestDto;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LoginController
{
    private const string REFRESH_COOKIE_NAME = 'refresh_token';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload] LoginRequestDto $requestDto,
        Request $request,
    ): JsonResponse {
        $result = $this->commandBus->dispatch(new AuthenticateAccountCommand(
            email: $requestDto->email,
            plainPassword: $requestDto->plainPassword,
            userAgent: $request->headers->get('User-Agent'),
            ipAddress: $request->getClientIp(),
        ));
        if (!$result instanceof AuthenticateAccountResult) {
            throw new \LogicException(sprintf(
                'Expected %s from authenticate account command bus dispatch.',
                AuthenticateAccountResult::class,
            ));
        }

        $account = $result->account();
        $response = new JsonResponse([
            'accessToken' => $result->accessToken(),
            'tokenType' => 'Bearer',
            'expiresIn' => $result->accessTokenExpiresInSeconds(),
            'account' => [
                'id' => $account->id(),
                'email' => $account->email(),
                'name' => $account->name(),
                'status' => $account->status(),
            ],
        ]);

        $response->headers->setCookie(Cookie::create(
            self::REFRESH_COOKIE_NAME,
            $result->rawRefreshToken(),
            $result->refreshTokenExpiresAt(),
            '/api/auth',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));

        return $response;
    }
}
