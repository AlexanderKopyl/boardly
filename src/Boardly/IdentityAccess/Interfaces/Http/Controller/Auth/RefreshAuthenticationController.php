<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationCommand;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationResult;
use App\Shared\Application\Bus\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RefreshAuthenticationController
{
    private const string REFRESH_COOKIE_NAME = 'refresh_token';
    private const string CSRF_INTENT_HEADER = 'X-CSRF-Intent';
    private const string REQUIRED_CSRF_INTENT = 'auth-refresh';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'], format: 'json')]
    #[OA\Post(
        path: '/api/auth/refresh',
        operationId: 'refreshAuthentication',
        summary: 'Refresh the access token using the refresh cookie',
        description: 'Issues a new JWT access token using the HttpOnly refresh_token cookie. The frontend must send this request with credentials enabled (credentials: "include"). The refresh_token cookie is rotated on success. Requires the X-CSRF-Intent header.',
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'X-CSRF-Intent',
                in: 'header',
                required: true,
                description: 'Must be exactly "auth-refresh". Prevents CSRF attacks on the cookie-based refresh flow.',
                schema: new OA\Schema(type: 'string', enum: ['auth-refresh']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed. Rotates the refresh_token cookie (HttpOnly, Secure, SameSite=None).',
                content: new OA\JsonContent(ref: '#/components/schemas/AccessTokenResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh token is missing, expired, revoked, or reused.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
            new OA\Response(
                response: 403,
                description: 'X-CSRF-Intent header is missing or incorrect. refresh_token cookie is cleared.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
            ),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        if (self::REQUIRED_CSRF_INTENT !== $request->headers->get(self::CSRF_INTENT_HEADER)) {
            return $this->csrfIntentFailureResponse();
        }

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
            Cookie::SAMESITE_NONE,
        ));

        return $response;
    }

    private function csrfIntentFailureResponse(): JsonResponse
    {
        $response = new JsonResponse(
            ['error' => ['code' => 'csrf_intent_required', 'message' => 'CSRF intent header is required.']],
            JsonResponse::HTTP_FORBIDDEN,
        );
        $response->headers->clearCookie(
            self::REFRESH_COOKIE_NAME,
            '/api/auth',
            null,
            true,
            true,
            Cookie::SAMESITE_NONE,
        );

        return $response;
    }
}
