<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationCommand;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationResult;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RefreshAuthenticationController
{
    private const string REFRESH_COOKIE_NAME = 'refresh_token';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'], format: 'json')]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new RefreshAuthenticationCommand(
            rawRefreshToken: (string) $request->cookies->get(self::REFRESH_COOKIE_NAME, ''),
            userAgent: $request->headers->get('User-Agent'),
            ipAddress: $request->getClientIp(),
        ));
        if (!$result instanceof RefreshAuthenticationResult) {
            throw new \LogicException(sprintf(
                'Expected %s from refresh authentication command bus dispatch.',
                RefreshAuthenticationResult::class,
            ));
        }

        $response = new JsonResponse([
            'accessToken' => $result->accessToken(),
            'tokenType' => 'Bearer',
            'expiresIn' => $result->accessTokenExpiresInSeconds(),
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
