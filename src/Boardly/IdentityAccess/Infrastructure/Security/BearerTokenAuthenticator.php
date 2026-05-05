<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenVerifierInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Throwable;

final class BearerTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const string BEARER_PREFIX = 'Bearer ';

    public function __construct(
        private AccessTokenVerifierInterface $accessTokenVerifier,
        private AccountRepositoryInterface $accounts,
        private AuthenticationFailureResponseFactory $failureResponseFactory,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return false;
        }

        return !$this->isPublicAuthEndpoint($request);
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = $this->extractBearerToken($request);

        try {
            $verifiedToken = $this->accessTokenVerifier->verify($token);
        } catch (Throwable) {
            throw $this->authenticationFailed();
        }

        $account = $this->accounts->find($verifiedToken->accountId());
        if (null === $account || !$account->status()->isActive()) {
            throw $this->authenticationFailed();
        }

        $user = AuthenticatedAccountUser::fromAccount($account);

        return new SelfValidatingPassport(new UserBadge(
            $user->getUserIdentifier(),
            static fn (): AuthenticatedAccountUser => $user,
        ));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->failureResponseFactory->create();
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->failureResponseFactory->create();
    }

    private function extractBearerToken(Request $request): string
    {
        $authorization = $request->headers->get('Authorization');
        if (null === $authorization || '' === trim($authorization)) {
            throw $this->authenticationFailed();
        }

        if (!str_starts_with($authorization, self::BEARER_PREFIX)) {
            throw $this->authenticationFailed();
        }

        $token = substr($authorization, strlen(self::BEARER_PREFIX));
        if ('' === trim($token)) {
            throw $this->authenticationFailed();
        }

        return $token;
    }

    private function isPublicAuthEndpoint(Request $request): bool
    {
        if ('POST' !== $request->getMethod()) {
            return false;
        }

        return in_array($request->getPathInfo(), [
            '/api/auth/login',
            '/api/auth/refresh',
            '/api/auth/logout',
            '/api/auth/register',
        ], true);
    }

    private function authenticationFailed(): AuthenticationException
    {
        return new CustomUserMessageAuthenticationException('Authentication required.');
    }
}
