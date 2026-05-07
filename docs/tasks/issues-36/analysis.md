# Issue #36 Analysis: Complete Authentication Baseline

Branch: `feature/36-phase-2-complete-authentication-flow`  
Scope: analysis and planning only

## 1. Current State

### Existing Account registration flow

The implemented registration flow is:

```text
POST /api/auth/register
-> RegisterAccountRequestDto
-> RegisterAccountController
-> CommandBusInterface
-> RegisterAccountCommand
-> RegisterAccountHandler
-> Account::register(...)
-> AccountRepositoryInterface::save(...)
-> OutboxInterface::store(AccountRegistered)
```

The controller is thin and depends on `CommandBusInterface`, not the concrete handler. It returns:

```json
{
  "accountId": "<uuid>",
  "status": "pending_approval"
}
```

Registration intentionally does not issue access tokens, create refresh tokens, or set cookies.

### Existing Account domain model and statuses

The IdentityAccess aggregate is `Account` in:

```text
src/Boardly/IdentityAccess/Domain/Model/Account.php
```

It owns:

```text
id
email
passwordHash
name
status
isSystemAdmin
createdAt
updatedAt
approvedAt
rejectedAt
disabledAt
```

Current statuses are implemented in `AccountStatus`:

```text
pending_approval
active
rejected
disabled
```

Current lifecycle behavior includes:

```text
register() -> pending_approval
createSystemAdmin() -> active system admin
approve() -> active
reject() -> rejected
disable() -> disabled
reconstitute() -> validates timestamp/status consistency
```

Only active accounts may receive access tokens or refresh cookies according to ADR-0005 and the authentication design docs.

### Existing password hashing approach

There is an application port:

```text
src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
```

It currently supports hashing only:

```php
hash(string $plainPassword): string
```

The infrastructure adapter is:

```text
src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php
```

It uses Symfony PasswordHasher behind the port. Login will also need password verification. The minimal direction is to extend this boundary with a verification method or introduce a dedicated `PasswordVerifierInterface`.

### Existing AccountRepository capabilities

`AccountRepositoryInterface` supports:

```text
save(Account)
get(AccountId)
find(AccountId)
findByEmail(Email)
existsByEmail(Email)
countActiveSystemAdmins()
```

This is enough for login credential lookup and refresh-time account status checks. It does not handle refresh sessions, which should remain separate from `Account`.

The Doctrine implementation persists `AccountEntity` to the `accounts` table and maps through `AccountMapper`.

### Existing HTTP auth controller conventions

The current auth controller convention is:

```text
Interfaces/Http/Controller/Auth/<UseCase>Controller
Interfaces/Http/Request/<UseCase>RequestDto
```

Controllers use Symfony attributes, `MapRequestPayload`, Symfony Validator constraints, `CommandBusInterface`, and explicit JSON response mapping.

### Existing API error handling conventions

`IdentityAccessApiExceptionSubscriber` maps `/api/*` exceptions to JSON:

```json
{
  "error": {
    "code": "code",
    "message": "Message."
  }
}
```

Currently mapped cases include:

```text
email_already_registered -> 409
validation_failed -> 422
invalid_request -> 400
InvalidEmail / InvalidAccountName -> 422
```

Authentication failures will need to extend this subscriber or a shared API error mapper with:

```text
invalid_credentials -> 401
account_not_active -> 403
invalid_refresh_token -> 401
invalid_or_expired_access_token -> 401
too_many_login_attempts -> 429, if rate limiting enters this issue
```

### Existing command bus/application handler conventions

Symfony Messenger is used as a synchronous application bus adapter:

```text
CommandBusInterface -> MessengerCommandBus -> command.bus
QueryBusInterface -> MessengerQueryBus -> query.bus
```

`MessengerCommandBus` unwraps single `HandlerFailedException` causes, so controlled application/domain exceptions can be mapped by HTTP subscribers.

Registration handlers are tagged explicitly on `command.bus` in `config/services.yaml`.

### Existing transaction boundary conventions

`TransactionalInterface` is implemented by:

```text
src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php
```

It wraps use-case operations in `EntityManagerInterface::wrapInTransaction()`.

`RegisterAccountHandler` performs account save and outbox store in one transaction. Login, refresh, and logout should follow the same convention for refresh-session persistence and token rotation/revocation.

### Existing `security.yaml` state

`config/packages/security.yaml` is still the Symfony skeleton configuration:

```text
password_hashers: PasswordAuthenticatedUserInterface -> auto
providers: users_in_memory
firewalls:
  dev: security false for profiler/assets
  main: lazy true, users_in_memory provider
access_control: commented examples only
```

There is no API firewall, no JWT authenticator, no IdentityAccess user provider, and no protected API access strategy yet.

### Existing packages available for JWT/security

`composer.json` includes Symfony Security:

```text
symfony/security-bundle
symfony/uid
symfony/serializer
symfony/validator
```

No JWT package is currently required in `composer.json`. Searches found no `lexik/jwt-authentication-bundle`, `lcobucci/jwt`, `firebase/php-jwt`, or `web-token/jwt-library` direct dependency.

Symfony Security can host a custom authenticator, but JWT encoding/signing/verification needs either:

```text
Option A: add a JWT library/bundle explicitly
Option B: implement signing/verification directly with PHP crypto primitives
```

Option A is recommended for production maintainability. The package choice is an implementation decision to make in Step 2, without changing composer files during this analysis step.

### Existing tests and test style

Current test coverage is layered:

```text
Domain unit tests:
  tests/Boardly/IdentityAccess/Domain/*

Application unit tests:
  tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php

HTTP/kernel tests:
  tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php

Doctrine mapper/repository unit and integration tests:
  tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/*

Outbox/message tests:
  tests/Boardly/IdentityAccess/Infrastructure/Outbox/*
```

The test style uses fast fakes for application handlers, `WebTestCase` for HTTP behavior, `KernelTestCase` for Doctrine integration, and explicit assertions that sensitive data is not exposed.

## 2. Problem

After registration, the authentication baseline is incomplete:

```text
- no login use case
- no password verification boundary
- no access token issuer
- no refresh token persistence
- no refresh-token rotation
- no refresh token reuse detection
- no logout/revocation use case
- no JWT authentication foundation for protected endpoints
- no IdentityAccess-aware user provider/authenticator
- no consistent auth failure behavior for login/refresh/protected routes
- no refresh/logout cookie behavior
- no security tests for token/session lifecycle
```

Registration creates pending accounts only. Active account authentication is not yet possible through the API.

## 3. Target Design

Implement a production-shaped but minimal IdentityAccess authentication baseline:

```text
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
JWT bearer authentication foundation for protected API requests
```

Core design:

```text
- login validates credentials and active account status
- login issues a short-lived JWT access token
- login creates an opaque refresh token and sets it as an HttpOnly cookie
- refresh tokens are stored server-side as hashes only
- refresh rotates tokens on every successful refresh
- replaced-token reuse revokes the refresh-token family and returns a generic 401
- logout revokes the current refresh token when possible and always clears the cookie
- Symfony Security authenticates protected API requests from Authorization: Bearer <jwt>
- authorization/RBAC remains out of scope
```

The access token should remain identity-only. Project, issue, workflow, and dynamic permissions must not be encoded into JWT claims.

## 4. Layering and Dependency Direction

Required layering:

```text
HTTP controller
-> request DTO / cookie extraction
-> application command
-> CommandBusInterface
-> command handler
-> application ports
-> domain model where business rules belong
-> infrastructure implementations behind ports
```

Rules for this issue:

```text
- controllers depend on CommandBusInterface, not concrete handlers
- application handlers depend on AccountRepositoryInterface and token/session ports
- domain does not know JWT, cookies, Symfony Security, Doctrine, or HTTP
- refresh-token persistence port belongs in the IdentityAccess application layer
- Doctrine refresh-session implementation belongs in IdentityAccess infrastructure
- JWT issuing/verifying, refresh-token generation, and refresh-token hashing belong behind application-level ports
- HTTP cookie creation/clearing belongs in the interface layer or a response factory used by controllers
- business state changes and refresh-session writes are committed through TransactionalInterface
- command/query buses remain synchronous for login, refresh, and logout
```

## 5. Use Cases

### AuthenticateAccountCommand / Handler / Result

Recommended placement:

```text
src/Boardly/IdentityAccess/Application/AuthenticateAccount/
```

Inputs:

```text
email: string
plainPassword: string
userAgent: nullable string, if metadata hashing is included
ipAddress: nullable string, if metadata hashing is included
```

Validation:

```text
- request DTO validates non-blank email/password
- Email value object normalizes email inside handler
- invalid email shape should become invalid_credentials for login, not validation detail leakage
```

Dependencies:

```text
AccountRepositoryInterface
Password verification port
AccessTokenIssuerInterface
RefreshTokenGeneratorInterface
RefreshTokenHasherInterface
RefreshSessionRepositoryInterface
ClockInterface
IdGeneratorInterface
TransactionalInterface
optional LoginRateLimiterInterface
```

Transaction boundary:

```text
Credential check can happen before or inside the transaction.
Refresh-session creation and persistence must happen in one transaction.
```

Expected result:

```text
accessToken
tokenType = Bearer
expiresIn = 900
rawRefreshTokenForCookie
refreshTokenExpiresAt
account DTO: id, email, name, status
```

Controlled failures:

```text
invalid_credentials -> 401
account_not_active -> 403
too_many_login_attempts -> 429, if rate limiter is included
```

Invalid credentials must not reveal whether the email or password was wrong.

### RefreshAuthenticationCommand / Handler / Result

Recommended placement:

```text
src/Boardly/IdentityAccess/Application/RefreshAuthentication/
```

Inputs:

```text
rawRefreshToken: string
userAgent: nullable string
ipAddress: nullable string
```

Validation:

```text
- controller checks cookie presence only enough to create command or raise controlled failure
- handler hashes raw token and performs authoritative lookup
```

Dependencies:

```text
RefreshTokenHasherInterface
RefreshTokenGeneratorInterface
RefreshSessionRepositoryInterface
AccountRepositoryInterface
AccessTokenIssuerInterface
ClockInterface
IdGeneratorInterface
TransactionalInterface
```

Transaction boundary:

```text
Find current session, reject invalid state, mark old session replaced, create new session, and save both in one transaction.
Reuse detection family revocation must also happen in a transaction.
```

Expected result:

```text
accessToken
tokenType = Bearer
expiresIn = 900
newRawRefreshTokenForCookie
refreshTokenExpiresAt
account DTO if response keeps parity with login; otherwise omit unless frontend needs it
```

Controlled failures:

```text
invalid_refresh_token -> 401 for missing, unknown, expired, revoked, replaced/reused, malformed token
```

On refresh failure, the HTTP layer should clear the refresh cookie and must not return a new access token.

### LogoutCommand / Handler

Recommended placement:

```text
src/Boardly/IdentityAccess/Application/Logout/
```

Inputs:

```text
rawRefreshToken: nullable string
```

Validation:

```text
No public failure for missing/unknown/expired/revoked tokens.
```

Dependencies:

```text
RefreshTokenHasherInterface
RefreshSessionRepositoryInterface
ClockInterface
TransactionalInterface
```

Transaction boundary:

```text
If a known session is found and is not already revoked, revoke it inside a transaction.
Missing or unknown token can return without mutation.
```

Expected result:

```text
No content or a minimal success result for controller mapping.
```

Controlled failures:

```text
None for normal token invalidity. Logout is idempotent.
```

HTTP always clears the refresh cookie.

## 6. Token Model

### Recommended persistence fields

Use a separate refresh-session/token table, for example `refresh_sessions`:

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

Additional recommended constraints/indexes:

```text
unique token_hash
index account_id
index family_id
index expires_at
index revoked_at
foreign key account_id -> accounts.id if compatible with current migration style
```

Rules:

```text
- raw refresh token must never be stored
- refresh token cookie contains the raw opaque token
- DB stores only token hash
- expired/revoked/replaced tokens are rejected
- refresh rotates token by creating a new record
- old replaced token cannot be used again
- replaced-token reuse revokes the family
- logout revokes current token
- access token is a short-lived JWT
- refresh token is a longer-lived opaque random token
```

### Modeling choice

The simplest architecture-compatible option is:

```text
IdentityAccess domain model:
  RefreshSession entity/value-object-supported lifecycle model

IdentityAccess application layer:
  RefreshSessionRepositoryInterface
  token generation/hash/JWT ports

IdentityAccess infrastructure:
  Doctrine RefreshSessionEntity / mapper / repository
```

Justification:

```text
- refresh-token lifecycle is IdentityAccess business behavior, not generic infrastructure
- it must protect invariants around rotation, replacement, revocation, and reuse detection
- it must remain outside the Account aggregate because Account must not own sessions
- persistence still belongs behind an application/domain-facing port
- cookies, JWT libraries, and raw token transport remain outside the domain
```

For minimal implementation, `RefreshSession` can start as a focused domain entity with explicit methods:

```text
create(...)
replaceWith(...)
revoke(...)
isExpired(...)
isRevoked()
wasReplaced()
```

Domain events for refresh sessions can remain deferred unless audit/security requirements demand them in this issue.

## 7. JWT Access Token

### Package/library options

Current `composer.json` does not include a JWT package. Symfony Security is present and can host the firewall/authenticator, but the JWT encode/decode/sign/verify implementation needs an explicit choice.

Recommended package direction for implementation planning:

```text
Add a maintained JWT library or Symfony JWT bundle explicitly.
```

Candidates to evaluate during implementation:

```text
lexik/jwt-authentication-bundle
lcobucci/jwt
firebase/php-jwt
web-token/jwt-library
```

Decision criteria:

```text
- Symfony 8 compatibility
- first-class asymmetric or HMAC signing support
- explicit validation of exp/iat/signature
- testability behind AccessTokenIssuerInterface / AccessTokenVerifierInterface
- low coupling between domain/application and package APIs
```

No package change should be made in this analysis step.

### Access token claims

Minimal claims:

```text
sub = account id
iat = issued-at timestamp
exp = expires-at timestamp
jti = token id
email = optional, only if useful for diagnostics/frontend
```

Avoid initially:

```text
status, unless the authenticator must cheaply reject disabled accounts and still reloads Account
roles, unless Symfony requires ROLE_USER as an authentication marker
project permissions
project roles
workflow permissions
issue permissions
allowed transitions
```

Recommended protected-route behavior:

```text
JWT authenticates identity only.
The authenticator maps sub -> AccountId and loads Account.
If the account is missing or not active, authentication fails with 401.
Use-case authorization remains separate and is not part of issue #36.
```

### Secret/key management and expiration

Plan explicit env/config values:

```text
BOARDLY_JWT_SIGNING_KEY or JWT private/public key paths, depending on library
BOARDLY_JWT_TTL_SECONDS=900
BOARDLY_REFRESH_TOKEN_TTL_SECONDS=2592000
BOARDLY_REFRESH_COOKIE_NAME=refresh_token
```

Invalid, malformed, incorrectly signed, or expired access tokens should return:

```text
401 Unauthorized
{
  "error": {
    "code": "unauthorized",
    "message": "Authentication is required."
  }
}
```

The exact error code can be `invalid_access_token` if the API error convention prefers specificity. Keep the public message generic.

## 8. Cookie Behavior

Recommended refresh cookie:

```text
Name: refresh_token
HttpOnly: true
Secure: true in prod
SameSite: Lax initially
Path: /api/auth
Domain: not set
Max-Age/Expires: aligned with refresh token expiration, initially 30 days
```

Notes:

```text
- the issue prompt suggests `boardly_refresh_token`; ADR-0005 and auth design use `refresh_token`
- prefer the accepted ADR name unless implementation discovers a conflict
- do not set Domain=.boardly.com
- do not expose refresh token in JSON
- clear cookie on logout and refresh failures
```

Local development may need a configuration switch for `Secure=false`, but production must keep `Secure=true`. SameSite may need to become `None; Secure` if the final `boardly.com -> api.boardly.com` browser behavior requires it.

## 9. Security Integration

Recommended Symfony Security direction:

```text
- add an API firewall for protected API routes
- keep /api/auth/register, /api/auth/login, /api/auth/refresh, /api/auth/logout public at firewall access-control level
- add a custom JWT authenticator or package authenticator for Authorization: Bearer
- add an IdentityAccess user/provider adapter that maps JWT sub to active Account
- return JSON 401 responses through an authentication entry point/failure handler
```

Protected API route strategy:

```text
- establish the foundation only
- do not implement broad RBAC
- do not encode permissions in JWT
- do not add Project/Task authorization
- do not add GET /api/me unless needed for manual verification or already planned in a separate issue
```

Minimal protected-route verification can be done with:

```text
Option A: a test-only route/controller in test environment
Option B: a tiny real diagnostic protected endpoint if product docs approve it
Option C: security/authenticator unit and functional tests without adding a product route
```

Recommendation: avoid adding `GET /api/me` in issue #36 unless the implementation cannot verify protected auth without it.

## 10. Error Handling

Controlled errors:

```text
invalid_credentials:
  401 for unknown email and wrong password
  must not reveal which part failed

account_not_active:
  403 for valid credentials but pending_approval/rejected/disabled account

missing refresh token:
  401 invalid_refresh_token on refresh
  204 on logout

invalid/unknown refresh token:
  401 invalid_refresh_token on refresh
  204 on logout

expired refresh token:
  401 invalid_refresh_token on refresh
  204 on logout

revoked refresh token:
  401 invalid_refresh_token on refresh
  204 on logout

replaced refresh token reuse:
  401 invalid_refresh_token
  revoke family
  clear cookie

invalid/expired access token:
  401 JSON error on protected routes
```

Use the existing JSON error shape:

```json
{
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Avoid logging raw passwords, raw refresh tokens, JWTs, or cookie values.

## 11. Test Strategy

Add coverage by layer.

Application tests:

```text
- successful login creates refresh session and returns access token/account data
- unknown email returns invalid_credentials
- wrong password returns invalid_credentials
- pending/rejected/disabled accounts cannot log in
- login uses password verification port
- login stores only refresh token hash
- login runs refresh-session persistence in a transaction
- refresh rotates token
- old refresh token cannot be reused after rotation
- replaced token reuse revokes family
- expired refresh token rejected
- revoked refresh token rejected
- non-active account cannot refresh
- logout revokes known current token
- logout is idempotent for missing/unknown/revoked token
```

Domain/model tests:

```text
- RefreshSession creation stores hash only
- RefreshSession replacement marks old session replaced
- RefreshSession revocation is idempotent or controlled
- expiration/revocation/replacement checks behave correctly
```

Infrastructure tests:

```text
- Doctrine refresh-session repository saves and reloads sessions
- token_hash unique constraint exists and is used for lookup
- no raw refresh token column exists
- family revocation updates all sessions in a family
- migration creates expected indexes/columns
- JWT issuer/verifier signs, verifies, and rejects expired/invalid tokens
- refresh token generator returns high-entropy opaque values
- refresh token hasher is deterministic and does not store raw token
```

HTTP/security tests:

```text
- successful login returns accessToken and account object
- login sets HttpOnly refresh-token cookie
- login response does not contain refreshToken or password data
- invalid credentials return controlled 401
- non-active account returns controlled 403
- refresh reads cookie and returns new access token
- refresh sets a new HttpOnly cookie
- refresh failure clears cookie
- logout returns controlled success and clears cookie
- protected route rejects missing bearer token
- protected route rejects invalid bearer token
- protected route rejects expired bearer token
- protected route accepts valid bearer token if a minimal verification route exists
- controllers depend on CommandBusInterface and do not bypass application layer
```

Quality gates:

```text
- PHP lint for new/changed PHP files
- container lint
- router/security/firewall debug checks
- Doctrine migration/test schema validation
- IdentityAccess PHPUnit suite
- full PHPUnit suite
- PHPStan
```

## 12. Risks and Decisions

### JWT package choice

Current composer dependencies do not provide an explicit JWT implementation. Implementation must choose and add one deliberately. This is already covered by ADR-0005 at the architecture level, so no new ADR is required unless the team wants to standardize on a specific JWT package/key type.

### Refresh token replay after rotation

Replaced-token reuse must be treated as a security signal. The plan should revoke the whole token family and return generic `invalid_refresh_token`.

### Concurrent refresh attempts

Two refresh requests can race with the same token. Mitigation should include transaction boundaries plus database constraints/locking or optimistic handling so only one rotation succeeds. The loser should receive `invalid_refresh_token` without issuing tokens.

### Token expiration defaults

ADR-0005 sets:

```text
access token: 15 minutes
refresh token: 30 days
```

Make these configurable, but keep the defaults.

### Cookie SameSite/Secure local-vs-prod behavior

Production must use `Secure=true`. Local HTTP development may need environment-specific behavior. SameSite starts as `Lax`, with a documented option to switch to `None; Secure` if cross-origin browser testing requires it.

### User agent/IP metadata

`user_agent_hash` and `ip_hash` are useful for diagnostics but not required for the minimal baseline. If included, store only hashes and avoid relying on them for strict validation because user agents and IPs can change.

### Device metadata

Device/session management UI should be postponed. Store only minimal metadata if it helps security diagnostics.

### Minimal protected test route

Do not add `GET /api/me` unless strictly needed for protected-route verification. Prefer authenticator tests or a test-only route if feasible.

### Refresh/logout idempotency

Logout should be idempotent. Refresh should not be idempotent because successful refresh rotates the token; repeated use of an old token should be rejected and may trigger family revocation.

### Keep scope from expanding into RBAC

Issue #36 should authenticate active accounts only. It must not introduce broad authorization, project permissions, workflow permissions, roles, issue visibility, OAuth, password reset, email verification, or Project/Task domain behavior.

### ADR gap

No new ADR is required for the baseline auth architecture. ADR-0005 already decides JWT access tokens plus opaque HttpOnly refresh cookies, token rotation, account lifecycle constraints, CORS/CSRF baseline, and frontend storage behavior.

An ADR would only be justified if the implementation locks in a major package/key-management strategy that the team wants to treat as binding beyond this issue.

### Login rate limiting deferral

Login rate limiting is intentionally deferred from issue #36.

This PR does not fully satisfy ADR-0005's login-rate-limiting requirement. ADR-0005 and the authentication API strategy require login attempts to be rate-limited by IP plus normalized email, with an initial policy of 5 failed attempts per 5 minutes per IP+email and a 429 `too_many_login_attempts` response.

The deferral is explicit because the current project does not have installed/configured limiter infrastructure. Implementing this correctly requires a new application port, controlled exception/error mapping, an infrastructure adapter, package/configuration decisions, failure accounting semantics, and application plus HTTP tests. That scope is larger than the current authentication baseline PR.

Follow-up security issue required before public production deployment:

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
- Add application and HTTP tests for allowed attempts, blocked attempts, invalid credentials accounting, and successful-login reset/clear behavior if supported by the selected limiter.
```
