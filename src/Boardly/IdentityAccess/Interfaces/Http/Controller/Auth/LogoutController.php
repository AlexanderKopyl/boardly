<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\Logout\LogoutCommand;
use App\Boardly\IdentityAccess\Application\Logout\LogoutResult;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LogoutController
{
    private const string REFRESH_COOKIE_NAME = 'refresh_token';
    private const string CSRF_INTENT_HEADER = 'X-CSRF-Intent';
    private const string REQUIRED_CSRF_INTENT = 'auth-refresh';

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'], format: 'json')]
    public function __invoke(Request $request): Response
    {
        if (self::REQUIRED_CSRF_INTENT !== $request->headers->get(self::CSRF_INTENT_HEADER)) {
            return $this->csrfIntentFailureResponse();
        }

        $result = $this->commandBus->dispatch(new LogoutCommand(
            rawRefreshToken: $request->cookies->get(self::REFRESH_COOKIE_NAME),
        ));
        if (!$result instanceof LogoutResult) {
            throw new \LogicException(sprintf(
                'Expected %s from logout command bus dispatch.',
                LogoutResult::class,
            ));
        }

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie(
            self::REFRESH_COOKIE_NAME,
            '/api/auth',
            null,
            true,
            true,
            Cookie::SAMESITE_LAX,
        );

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
            Cookie::SAMESITE_LAX,
        );

        return $response;
    }
}
