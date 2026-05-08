# Authentication API Strategy

## Status

Accepted design direction.

## Date

2026-05-02

## Related issue

```text
#5 Design authentication API strategy
```

## Related ADRs and rules

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
docs/architecture/project-architecture-rules.md
```

## Goal

Define the initial authentication API strategy for Boardly.

The design must support:

```text
- API-first Symfony backend
- Next.js frontend
- admin-approved account lifecycle
- short-lived access tokens
- secure refresh token handling
- clear IdentityAccess boundary
- predictable route contracts
- production-aware browser security behavior
```

## Non-goals

This document does not implement production code.

Out of scope for the first authentication milestone:

```text
- public self-service password reset
- email verification flow
- MFA
- OAuth/social login
- SSO/SAML/OIDC enterprise login
- session/device management UI
- workspace-level account approval
- project-level account approval
- complete admin UI
```

Password reset is expected to be admin-controlled and designed later as a separate issue.

## Deployment model

Production deployment direction:

```text
https://boardly.com      -> Next.js frontend
https://api.boardly.com  -> Symfony API
```

This is cross-origin from the browser point of view.

Implications:

```text
- CORS must be explicit
- wildcard CORS is forbidden when credentials are used
- refresh cookies are issued by api.boardly.com
- frontend requests requiring cookies must use credentials/include behavior
- JWT-authenticated requests must use Authorization: Bearer <accessToken>
```

## Authentication model

Boardly uses:

```text
short-lived JWT access token
+
opaque HttpOnly Secure refresh token cookie
```

### Access token

Purpose:

```text
Authenticate API requests after login or refresh.
```

Rules:

```text
- JWT
- lifetime: 15 minutes
- returned in JSON from login and refresh
- stored only in frontend memory
- sent as Authorization: Bearer <accessToken>
```

Initial response shape:

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

Minimal JWT claims:

```text
sub   = account id / actor id
iat   = issued at
exp   = expires at
jti   = token id
email = optional
```

JWT must not contain dynamic authorization data:

```text
- project permissions
- issue permissions
- workflow permissions
- allowed transitions
- project roles
```

JWT establishes identity only. Authorization remains backend-authoritative and use-case specific.

### Refresh token

Purpose:

```text
Create a new access token without forcing the user to login again.
```

Rules:

```text
- opaque random token
- not a JWT
- stored in HttpOnly Secure cookie
- never returned in JSON
- database stores only token hash
- lifetime: 30 days
- rotated on every successful refresh
```

Initial cookie direction:

```text
Name: refresh_token
Host: api.boardly.com
Domain: not set
Path: /api/auth
HttpOnly: true
Secure: true
SameSite: Lax initially
```

Do not set:

```text
Domain=.boardly.com
```

Reason:

```text
The refresh token is needed only by api.boardly.com and must not be scoped to all Boardly subdomains.
```

Implementation must verify browser behavior for requests from `boardly.com` to `api.boardly.com`. If cookies are not sent reliably with `SameSite=Lax`, switch the refresh cookie to:

```text
SameSite=None; Secure
```

## IdentityAccess boundary

Authentication and account lifecycle belong to:

```text
src/Boardly/IdentityAccess/
```

Initial domain concepts:

```text
Account
AccountId
AccountStatus
RefreshToken / AuthSession
```

Use `Account` as the primary domain entity name, not `User`.

Reason:

```text
Account represents authentication identity, credentials, lifecycle status, and login eligibility.
User is too generic and often becomes a God Entity.
```

The API may still use user-facing words when useful for frontend clarity, but the IdentityAccess domain model should use Account.

### IdentityAccess owns

```text
- account identity
- credentials
- account status
- login eligibility
- refresh token/session lifecycle
- authentication-related account lifecycle rules
```

### IdentityAccess domain must not depend on

```text
- Symfony Security
- JWT libraries
- HTTP cookies
- Doctrine
- controllers
- CORS implementation
- CSRF implementation
```

These are infrastructure/interface adapter concerns.

## Account lifecycle

Initial statuses:

```text
pending_approval
active
rejected
disabled
```

Meaning:

```text
pending_approval = registered but waiting for system admin approval
active           = approved and login is allowed
rejected         = account request was rejected and login is denied
disabled         = previously active account was disabled and login is denied
```

Only active accounts can receive:

```text
- JWT access token
- refresh_token cookie
```

Registration is account request/creation.
Admin approval activates the account.
Login creates an authenticated session.

## Initial routes

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
GET  /api/auth/me
```

Admin approval routes:

```text
GET  /api/admin/accounts/pending
POST /api/admin/accounts/{accountId}/approve
POST /api/admin/accounts/{accountId}/reject
```

Admin approval API should be documented in this design, but implementation may be handled as a separate issue.

## Route contracts

### POST /api/auth/register

Purpose:

```text
Create a new Account in pending_approval status.
```

Request:

```json
{
  "email": "user@example.com",
  "plainPassword": "plain-password",
  "name": "Alex"
}
```

Success:

```text
201 Created
```

Response:

```json
{
  "id": "account-id",
  "email": "user@example.com",
  "name": "Alex",
  "status": "pending_approval"
}
```

Rules:

```text
- create Account
- set status to pending_approval
- do not issue accessToken
- do not create refresh token/session
- do not set refresh_token cookie
```

Validation failure:

```text
422 Unprocessable Entity
error code: validation_failed
```

Duplicate email:

```text
409 Conflict
error code: email_already_registered
```

### POST /api/auth/login

Purpose:

```text
Create authenticated session for an active account.
```

Request:

```json
{
  "email": "user@example.com",
  "plainPassword": "plain-password"
}
```

Success:

```text
200 OK
```

Response:

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

Cookie side effect:

```text
Set-Cookie: refresh_token=<opaque-token>; HttpOnly; Secure; Path=/api/auth
```

Behavior:

```text
- validate credentials
- check account status
- allow login only if account status is active
- create refresh token/session
- store only refresh token hash
- return 15-minute JWT access token
- set HttpOnly Secure refresh_token cookie
```

Invalid credentials:

```text
401 Unauthorized
error code: invalid_credentials
```

Valid credentials but non-active account:

```text
403 Forbidden
error code: account_not_active
```

Rate limit exceeded:

```text
429 Too Many Requests
error code: too_many_login_attempts
```

### POST /api/auth/refresh

Purpose:

```text
Rotate refresh token and issue a new access token.
```

Request body:

```text
none
```

Required browser state:

```text
refresh_token cookie
```

Required header:

```http
X-CSRF-Intent: auth-refresh
```

Success:

```text
200 OK
```

Response:

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

Behavior:

```text
- validate current refresh_token cookie
- reject expired/revoked/invalid/unknown/reused token
- invalidate old refresh token
- create new opaque refresh token
- store only new token hash
- set new refresh_token cookie
- return new 15-minute JWT access token
```

Failure cases:

```text
missing
expired
revoked
invalid
unknown
reused
```

Public failure response:

```text
401 Unauthorized
error code: invalid_refresh_token
```

On failure:

```text
- clear refresh_token cookie
- do not issue new access token
- do not issue new refresh token
```

If reuse is detected:

```text
- reject refresh
- revoke whole token family/session chain
- require re-login
- return generic invalid_refresh_token response
```

### POST /api/auth/logout

Purpose:

```text
End current refresh session if it exists and clear the refresh cookie.
```

Required header:

```http
X-CSRF-Intent: auth-refresh
```

Success response:

```text
204 No Content
```

Behavior:

```text
If refresh_token cookie exists and maps to a valid active refresh token/session:
- revoke current refresh token/session
- clear refresh_token cookie
- return 204 No Content

If refresh_token cookie is missing, expired, already revoked, invalid, or unknown:
- clear refresh_token cookie anyway
- return 204 No Content
```

Rules:

```text
- logout is idempotent
- do not leak token validity state
- do not issue new tokens during logout
```

### GET /api/auth/me

Purpose:

```text
Return basic authenticated account identity for the current access token.
```

Requires:

```http
Authorization: Bearer <accessToken>
```

Success:

```text
200 OK
```

Response:

```json
{
  "id": "account-id",
  "email": "admin@example.com",
  "name": "Admin"
}
```

Failure:

```text
401 Unauthorized
```

Rules:

```text
- return only basic identity/profile information
- do not return permission matrix
- do not return project roles
- do not return workflow permissions
- do not return issue permissions
- do not return allowed transitions
```

### GET /api/admin/accounts/pending

Purpose:

```text
List accounts waiting for system admin approval.
```

Access:

```text
system admin only
```

Success:

```text
200 OK
```

Initial response shape:

```json
{
  "items": [
    {
      "id": "account-id",
      "email": "user@example.com",
      "name": "Alex",
      "status": "pending_approval",
      "registeredAt": "2026-05-02T12:00:00+00:00"
    }
  ]
}
```

Rules:

```text
- include only pending_approval accounts
- exclude active, rejected, and disabled accounts
```

### POST /api/admin/accounts/{accountId}/approve

Purpose:

```text
Approve a pending account and allow future login.
```

Access:

```text
system admin only
```

Success:

```text
204 No Content
```

Behavior:

```text
pending_approval -> active
```

Rules:

```text
- do not issue tokens during approval
- user must still login normally after approval
```

### POST /api/admin/accounts/{accountId}/reject

Purpose:

```text
Reject a pending account request.
```

Access:

```text
system admin only
```

Success:

```text
204 No Content
```

Behavior:

```text
pending_approval -> rejected
```

Rules:

```text
- rejected accounts cannot login
- do not delete the account record silently
```

## First system admin bootstrap

The first system admin is created via CLI command, not public registration.

Initial command direction:

```bash
php bin/console boardly:identity:create-system-admin admin@example.com
```

Rules:

```text
- do not create first system admin through public API
- do not auto-promote the first registered account
- do not expose unauthenticated admin bootstrap endpoint
```

The command should create an active Account with system admin capability/role.

Password setup mechanics are implementation details and may be interactive password input or a secure one-time setup flow.

## Error response format

### Validation error

```text
422 Unprocessable Entity
```

```json
{
  "error": {
    "code": "validation_failed",
    "message": "Validation failed.",
    "fields": {
      "email": ["Invalid email."],
      "plainPassword": ["Password is too short."]
    }
  }
}
```

### Duplicate email

```text
409 Conflict
```

```json
{
  "error": {
    "code": "email_already_registered",
    "message": "Email is already registered."
  }
}
```

### Invalid credentials

```text
401 Unauthorized
```

```json
{
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Do not reveal whether:

```text
- email does not exist
- password is wrong
```

### Account not active

```text
403 Forbidden
```

```json
{
  "error": {
    "code": "account_not_active",
    "message": "Account is not active."
  }
}
```

Applies to:

```text
pending_approval
rejected
disabled
```

### Invalid refresh token

```text
401 Unauthorized
```

```json
{
  "error": {
    "code": "invalid_refresh_token",
    "message": "Invalid refresh token."
  }
}
```

Applies to:

```text
missing
expired
revoked
invalid
unknown
reused
```

### Too many login attempts

```text
429 Too Many Requests
```

```json
{
  "error": {
    "code": "too_many_login_attempts",
    "message": "Too many login attempts. Please try again later."
  }
}
```

## Application layer direction

Initial use cases may be organized as:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/
src/Boardly/IdentityAccess/Application/Login/
src/Boardly/IdentityAccess/Application/RefreshSession/
src/Boardly/IdentityAccess/Application/Logout/
src/Boardly/IdentityAccess/Application/GetCurrentAccount/
src/Boardly/IdentityAccess/Application/ApproveAccount/
src/Boardly/IdentityAccess/Application/RejectAccount/
```

Example commands/queries:

```text
RegisterAccountCommand
LoginCommand
RefreshSessionCommand
LogoutCommand
GetCurrentAccountQuery
ApproveAccountCommand
RejectAccountCommand
```

The application layer orchestrates:

```text
- validation of command/query input
- loading Account / AuthSession
- permission checks
- password verification through a port
- token issuing through a port
- refresh token generation/hash through ports
- persistence transaction boundary
- returning response DTO/read model
```

## Ports direction

Initial application ports may include:

```text
AccountRepositoryInterface
RefreshSessionRepositoryInterface
PasswordHasherInterface
JwtTokenIssuerInterface
OpaqueTokenGeneratorInterface
RefreshTokenHasherInterface
ClockInterface
IdGeneratorInterface
LoginRateLimiterInterface
```

Notes:

```text
- ClockInterface and IdGeneratorInterface may reuse shared application ports
- Symfony PasswordHasher should be used behind an adapter
- JWT implementation should be behind an adapter
- Redis-based rate limiting can be hidden behind LoginRateLimiterInterface
```

## Persistence direction

### Account

Initial Account persistence needs:

```text
id
email
passwordHash
name
status
systemAdmin flag/capability/role
createdAt
updatedAt
approvedAt nullable
rejectedAt nullable
disabledAt nullable
```

Email must be unique inside IdentityAccess.

### Refresh session/token

Initial refresh token/session persistence needs:

```text
id
accountId
tokenHash
familyId
replacedByTokenId nullable
expiresAt
revokedAt nullable
createdAt
lastUsedAt nullable
userAgentHash optional
ipAddressHash optional
```

Rules:

```text
- store only token hash
- never store raw refresh token
- rotate token on every successful refresh
- track token family for reuse detection
```

## Password hashing

Use Symfony PasswordHasher with:

```text
algorithm=auto
```

Rules:

```text
- do not hash passwords manually
- do not store plain-text passwords
- do not use md5, sha1, or raw sha256
- do not verify passwords inside controllers
```

Password hashing must be accessed through an application/infrastructure boundary. IdentityAccess domain must not depend on Symfony components.

## Rate limiting

Login must be rate-limited by:

```text
IP + email
```

Initial conceptual limit:

```text
5 failed attempts per 5 minutes per IP+email
```

After exceeding the limit:

```text
429 Too Many Requests
error code: too_many_login_attempts
```

Redis is a likely implementation candidate, but the application should depend on a rate limiter port rather than Redis directly.

## CORS direction

Production CORS:

```text
Allowed origin: https://boardly.com
Allow credentials: true
Allowed methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Allowed headers: Authorization, Content-Type, X-CSRF-Intent
```

Rules:

```text
- do not use Access-Control-Allow-Origin: * with credentials
- do not allow arbitrary origins
- allow Authorization header for JWT API requests
- allow X-CSRF-Intent for refresh/logout
```

## CSRF baseline

Cookie-based auth endpoints require additional protection because browsers attach cookies automatically.

Affected endpoints:

```text
POST /api/auth/refresh
POST /api/auth/logout
```

Initial baseline:

```text
- SameSite=Lax refresh cookie initially
- require custom header
```

Required header direction:

```http
X-CSRF-Intent: auth-refresh
```

Rules:

```text
- do not rely only on the refresh_token cookie
- do not allow simple cross-site form submissions to trigger refresh/logout
- revisit full CSRF token strategy if deployment topology changes
```

## Frontend behavior

The Next.js frontend must:

```text
- keep accessToken only in memory
- send Authorization: Bearer <accessToken> for authenticated API requests
- call POST /api/auth/refresh after browser reload
- use credentials/include for refresh/logout requests to api.boardly.com
- include X-CSRF-Intent header for refresh/logout
- clear local auth state on logout
- clear local auth state after invalid_refresh_token
```

The frontend must not store access tokens in:

```text
- localStorage
- sessionStorage
- IndexedDB
- JavaScript-readable cookies
```

Suggested startup flow:

```text
1. App loads.
2. accessToken memory state is empty.
3. Frontend calls POST /api/auth/refresh with credentials/include.
4. If 200, store returned accessToken in memory and call /api/auth/me.
5. If 401 invalid_refresh_token, keep user unauthenticated.
```

Suggested 401 flow for authenticated API calls:

```text
1. API request returns 401 because access token expired.
2. Frontend calls POST /api/auth/refresh.
3. If refresh succeeds, retry original request once.
4. If refresh fails, clear local auth state and redirect to login.
```

## Testing direction

Initial tests should cover:

```text
- register creates pending_approval account
- register does not issue tokens
- duplicate email returns 409
- validation errors return 422
- login with invalid credentials returns 401
- login with pending_approval account returns 403
- login with active account returns access token and refresh cookie
- refresh rotates refresh token
- refresh stores only token hash
- refresh reuse revokes token family
- refresh failure clears cookie
- logout is idempotent
- /api/auth/me requires valid access token
- /api/auth/me does not return permissions matrix
- admin approval requires system admin
- approve changes pending_approval to active
- reject changes pending_approval to rejected
- non-system-admin cannot approve/reject
- login rate limit returns 429
```

## Common implementation mistakes to avoid

```text
- returning refresh_token in JSON
- storing accessToken in localStorage
- storing raw refresh tokens in the database
- putting permissions matrix inside JWT
- exposing Doctrine entities directly as API responses
- putting authentication logic directly in controllers
- letting public registration auto-login users
- making first registered user system admin
- mixing account approval with project permissions
- using wildcard CORS with credentials
- relying only on cookies for refresh/logout without CSRF baseline
- making logout reveal refresh token validity state
```

## Open implementation decisions

The following details are intentionally left for implementation issues:

```text
- exact JWT library/bundle
- exact Doctrine mappings
- exact Symfony security firewall setup
- exact password rules beyond baseline validation
- exact system admin capability model
- exact admin password reset flow
- exact audit events for auth/session/account approval
- exact Redis/rate limiter implementation
```

These decisions must still respect ADR-0005 and this design document.
