# Issue #36 Plan: Complete Authentication Baseline

Branch: `feature/36-phase-2-complete-authentication-flow`  
Scope: implementation plan only

## Commit Plan

Use small commits. Keep each commit independently reviewable and covered by focused tests.

## Step 1: Add refresh token persistence model and migration

Goal:

```text
Persist refresh-token/session lifecycle separately from Account.
Store only token hashes.
```

Implementation tasks:

```text
- add RefreshSession domain model/value objects or minimal lifecycle model
- add RefreshSessionRepositoryInterface in IdentityAccess application layer
- add Doctrine RefreshSessionEntity, mapper, and repository
- add migration for refresh_sessions table
- add indexes/constraints for token_hash, account_id, family_id, expiration/revocation lookup
- add family revocation repository behavior
```

Recommended table fields:

```text
id
account_id
token_hash
family_id
expires_at
revoked_at nullable
replaced_by_token_id nullable
created_at
last_used_at nullable
user_agent_hash nullable
ip_hash nullable
```

Tests:

```text
- RefreshSession lifecycle unit tests
- mapper tests
- repository unit tests
- repository integration tests
- migration/schema expectations
- assertion that no raw refresh token is stored
```

Acceptance for this step:

```text
- refresh-session state is separate from accounts
- token_hash is the persisted lookup value
- raw token has no column, entity field, or public accessor
- repository can save, find by hash, rotate/replace, revoke, and revoke family
```

## Step 2: Add token abstractions

Goal:

```text
Keep JWT issuing, refresh-token generation, and refresh-token hashing behind ports.
```

Application ports:

```text
AccessTokenIssuerInterface
AccessTokenVerifierInterface, if authenticator verification needs a separate port
RefreshTokenGeneratorInterface
RefreshTokenHasherInterface
Password verification port or extended PasswordHasherInterface::verify(...)
```

Infrastructure implementations:

```text
JWT issuer/verifier backed by chosen package
secure random opaque refresh token generator
HMAC/SHA-256 or password-hasher-backed refresh token hasher
Symfony password verifier adapter using PasswordHasherFactoryInterface
```

Config/env decisions:

```text
JWT signing key or key paths
access token TTL, default 900 seconds
refresh token TTL, default 2592000 seconds
refresh cookie name, default refresh_token
refresh cookie Secure/SameSite/Path settings
```

Package decision:

```text
- composer currently has no explicit JWT package
- choose a maintained Symfony 8-compatible JWT package/library
- add package in implementation, not in this planning step
```

Tests:

```text
- access token contains sub, iat, exp, jti and verifies successfully
- expired/invalid token verification fails
- refresh token generator returns opaque high-entropy tokens
- refresh token hasher is deterministic for lookup
- hasher output does not equal raw token
- password verification accepts valid hash/password and rejects wrong password
```

## Step 3: Add AuthenticateAccount use case

Goal:

```text
Authenticate an active account and create a refresh session.
```

Files/concepts:

```text
AuthenticateAccountCommand
AuthenticateAccountHandler
AuthenticateAccountResult
InvalidCredentials
AccountNotActive
```

Command inputs:

```text
email
plainPassword
userAgent nullable
ipAddress nullable
```

Handler flow:

```text
1. Normalize email through Email value object.
2. Find account by email.
3. For missing account, fail with invalid_credentials.
4. Verify password through password verification port.
5. For wrong password, fail with invalid_credentials.
6. Require AccountStatus active.
7. Issue short-lived access token.
8. Generate raw opaque refresh token.
9. Hash refresh token.
10. Create RefreshSession metadata with account id, family id, expiration, createdAt.
11. Persist refresh session in a transaction.
12. Return access token, account DTO, and raw refresh token for immediate cookie creation.
```

Transaction boundary:

```text
RefreshSession creation and persistence must be inside TransactionalInterface.
```

Outbox decision:

```text
Login creates RefreshSession transactionally but intentionally does not publish or store an outbox event in issue #36.
Do not add AccountAuthenticated, LoginSucceeded, or RefreshSessionCreated in this PR.
Auth audit/session events require separate explicit design before they become outbox events.
```

Required follow-up design if audit/security/integration visibility is needed:

```text
- event name and owner
- payload and PII policy
- outbox serializer/mapper
- consumer/idempotency strategy
- retention/monitoring expectations
```

Tests:

```text
- successful active account login
- unknown email returns invalid_credentials
- wrong password returns invalid_credentials
- pending_approval account returns account_not_active
- rejected account returns account_not_active
- disabled account returns account_not_active
- password verification port is used
- access token issuer is used
- refresh token generated and hashed
- raw refresh token is not persisted
- refresh session persisted in transaction
- result exposes no password hash
```

## Step 4: Add login HTTP endpoint

Goal:

```text
Expose POST /api/auth/login.
```

Files/concepts:

```text
LoginAccountRequestDto or AuthenticateAccountRequestDto
Login controller under Interfaces/Http/Controller/Auth
refresh cookie response factory/helper if useful
IdentityAccessApiExceptionSubscriber mappings
```

Request shape required by issue #36:

```json
{
  "email": "user@example.com",
  "plainPassword": "Password123!"
}
```

Response shape required by issue #36:

```json
{
  "accessToken": "<jwt>",
  "account": {
    "id": "<account-id>",
    "email": "user@example.com",
    "name": "User Name",
    "status": "active"
  }
}
```

Recommended optional fields if keeping ADR response metadata:

```text
tokenType = Bearer
expiresIn = 900
```

Cookie:

```text
Set-Cookie: refresh_token=<opaque>; HttpOnly; Secure; SameSite=Lax; Path=/api/auth
```

Controller rules:

```text
- use request DTO and Symfony Validator
- map request DTO to AuthenticateAccountCommand
- call CommandBusInterface
- set cookie from application result
- do not verify password or issue JWT in controller
```

Tests:

```text
- POST /api/auth/login succeeds for active account
- response contains accessToken and account object
- response does not contain refreshToken/password/passwordHash
- response sets HttpOnly refresh_token cookie
- cookie has expected Path/SameSite/Secure behavior
- invalid credentials return 401 JSON
- non-active account returns 403 JSON
- malformed/invalid payload returns existing 400/422 shape
```

Explicit deferral:

```text
Login rate limiting is intentionally deferred from issue #36.

This PR does not fully satisfy ADR-0005's login-rate-limiting requirement.
The missing limiter is a security-required follow-up before public production deployment.
```

Recommended follow-up scope:

```text
- Add LoginRateLimiterInterface application port.
- Rate limit by IP + normalized email.
- Use initial policy: 5 failed attempts per 5 minutes per IP+email.
- Add TooManyLoginAttempts exception.
- Map TooManyLoginAttempts to HTTP 429:
  {
    "error": {
      "code": "too_many_login_attempts",
      "message": "Too many login attempts. Please try again later."
    }
  }
- Keep the application layer independent from Redis and Symfony RateLimiter.
- Implement the infrastructure adapter with Symfony RateLimiter or Redis-backed storage.
- Add application and HTTP tests.
```

## Step 5: Add refresh use case and endpoint

Goal:

```text
Rotate refresh tokens and issue a new access token.
```

Files/concepts:

```text
RefreshAuthenticationCommand
RefreshAuthenticationHandler
RefreshAuthenticationResult
Refresh authentication controller
InvalidRefreshToken
```

Handler flow:

```text
1. Receive raw refresh token from controller.
2. Hash raw token through RefreshTokenHasherInterface.
3. Find RefreshSession by hash.
4. Reject missing, unknown, expired, revoked, or replaced tokens.
5. If replaced token is seen, revoke token family and fail invalid_refresh_token.
6. Load Account by session.accountId.
7. Reject if account is missing or not active.
8. Generate new raw refresh token.
9. Hash new refresh token.
10. Mark old session replaced.
11. Create new session in same family.
12. Persist old and new sessions in one transaction.
13. Issue new access token.
14. Return new raw refresh token for cookie.
```

Endpoint:

```text
POST /api/auth/refresh
```

Cookie/header behavior:

```text
- read refresh_token cookie
- require X-CSRF-Intent: auth-refresh if implementing ADR-0005 CSRF baseline in this issue
- on success set new refresh_token cookie
- on failure clear refresh_token cookie
```

Tests:

```text
- successful refresh returns new access token
- successful refresh sets new HttpOnly cookie
- old session is replaced and new session is stored
- old refresh token cannot be reused after rotation
- replaced-token reuse revokes family
- missing cookie returns 401 invalid_refresh_token and clears cookie
- unknown token returns 401 invalid_refresh_token and clears cookie
- expired token rejected
- revoked token rejected
- non-active account cannot refresh
- raw token is never persisted or returned in JSON
- concurrent refresh behavior is controlled by repository/transaction tests where feasible
```

## Step 6: Add logout use case and endpoint

Goal:

```text
Revoke current refresh session when possible and clear cookie.
```

Files/concepts:

```text
LogoutCommand
LogoutHandler
Logout controller
```

Handler flow:

```text
1. Accept nullable raw refresh token.
2. If missing, return success.
3. Hash token.
4. Find refresh session by hash.
5. If found and not already revoked, revoke it in a transaction.
6. Return success.
```

Endpoint:

```text
POST /api/auth/logout
```

Response:

```text
204 No Content
```

Cookie behavior:

```text
Always clear refresh_token cookie.
```

Tests:

```text
- logout revokes known current token
- logout clears cookie
- missing cookie still returns 204 and clears cookie
- unknown token still returns 204 and clears cookie
- already revoked token still returns 204 and clears cookie
- response does not reveal whether token existed
```

## Step 7: Add JWT security integration foundation

Goal:

```text
Authenticate protected API requests with Authorization: Bearer <jwt>.
```

Implementation tasks:

```text
- choose package/custom authenticator path from Step 2
- add API firewall/authenticator in security.yaml
- configure public auth endpoints
- map JWT sub claim to AccountId
- load Account through AccountRepositoryInterface or a Security user provider adapter
- reject missing, invalid, expired, unknown-account, and non-active-account tokens with JSON 401
- keep authorization/RBAC out of scope
```

Protected-route verification strategy:

```text
Prefer authenticator functional tests or a test-only protected route.
Do not add GET /api/me unless implementation proves it is required.
```

Tests:

```text
- protected route rejects missing access token
- protected route rejects malformed access token
- protected route rejects expired access token
- protected route rejects token for missing account
- protected route rejects token for disabled/non-active account
- protected route accepts valid token for active account if a minimal verification route exists
- access token does not need project/workflow/issue permissions
```

## Step 8: Full verification

Run targeted linting for every new/changed PHP file:

```bash
php -l path/to/new-or-changed-file.php
```

Run Symfony checks:

```bash
php bin/console lint:container
php bin/console debug:router
php bin/console debug:config security
php bin/console debug:firewall
```

Run Doctrine checks:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=test
php bin/console doctrine:schema:validate --env=test
```

Run tests/static analysis:

```bash
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess
php ./vendor/bin/phpunit
php /opt/homebrew/bin/composer phpstan
```

Run architecture searches:

```bash
rg "MessageBusInterface" src/Boardly src/Shared
rg "JWT|Token|RefreshToken|PasswordHasher|Authenticator" src/Boardly/IdentityAccess src/Shared config
rg "plainPassword|refreshToken|token_hash|HttpOnly|SameSite" src tests config
rg "AuthenticateAccount|RefreshAuthentication|Logout" src tests
```

Expected search outcomes:

```text
- controllers use CommandBusInterface, not MessageBusInterface
- domain does not reference JWT/cookies/Symfony Security/Doctrine
- raw refresh token only appears in command/result/controller boundary where needed for cookie creation
- token_hash appears in persistence only
- AuthenticateAccount, RefreshAuthentication, and Logout have tests
```

## Acceptance Criteria

```text
- registered active account can log in
- invalid credentials return controlled error without leaking email/password distinction
- inactive/disabled accounts cannot log in
- login returns short-lived JWT access token
- login sets opaque HttpOnly refresh token cookie
- raw refresh token is not stored
- refresh endpoint rotates refresh tokens
- old refresh token is rejected after rotation
- logout revokes current refresh token and clears cookie
- expired/revoked refresh tokens are rejected
- invalid/expired access tokens return 401 on protected routes
- controllers do not bypass application layer
- command/query buses remain synchronous
- no broad authorization/RBAC implemented
- tests cover success and failure flows
```

## Non-Goals

```text
- Do not implement GET /api/me unless required only for minimal protected-route verification.
- Do not implement Project/Task domain.
- Do not add OAuth/social login.
- Do not add password reset.
- Do not add email verification.
- Do not expose refresh token in JSON responses.
- Do not implement broad authorization rules.
- Do not introduce roles/permissions beyond what JWT authentication minimally needs.
- Do not make application commands async.
- Do not move business logic into controllers.
- Do not implement login rate limiting in issue #36; defer it to a separate security follow-up required before public production deployment.
```

## Commit Breakdown

Suggested commits:

```text
1. Add refresh-session model, Doctrine persistence, migration, and tests
2. Add token/password verification ports and infrastructure adapters
3. Add AuthenticateAccount command handler and application tests
4. Add login request/controller/cookie response and HTTP tests
5. Add RefreshAuthentication command handler/controller and tests
6. Add Logout command handler/controller and tests
7. Add JWT authenticator/firewall foundation and security tests
8. Run verification, fix integration issues, and update task notes if needed
```
