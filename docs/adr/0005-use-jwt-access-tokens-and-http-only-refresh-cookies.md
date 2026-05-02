# ADR-0005: Use JWT Access Tokens and HttpOnly Refresh Cookies

## Status

Accepted

## Date

2026-05-02

## Context

Boardly is an API-first project and task management system.

According to ADR-0004, the primary product UI is a Next.js frontend and Symfony owns the backend HTTP API, application layer, and domain behavior.

The production deployment model for the initial product is:

```text
https://boardly.com      -> Next.js frontend
https://api.boardly.com  -> Symfony API
```

This means the frontend and backend are separate origins. Authentication must therefore handle browser security constraints explicitly:

- access token storage
- refresh token storage
- cookie scope
- CORS
- CSRF baseline
- token rotation
- logout
- account lifecycle
- frontend reload behavior

Boardly also uses admin-approved accounts. Public registration creates an account request, but it does not grant product access until a system admin approves the account.

## Decision

Boardly will use:

```text
short-lived JWT access tokens
+
opaque HttpOnly Secure refresh tokens stored in cookies
```

### Access tokens

JWT access tokens are used for authenticated API requests.

Rules:

```text
- access tokens are short-lived
- initial lifetime: 15 minutes
- access tokens are returned in JSON from login and refresh
- frontend stores the access token only in memory
- frontend sends the access token using Authorization: Bearer <token>
```

Initial successful login/refresh response:

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

JWT claims must stay minimal:

```text
sub   = account id / actor id
iat   = issued at
exp   = expires at
jti   = token id
email = optional
```

JWT claims must not contain dynamic authorization data such as:

```text
- project permissions
- workflow permissions
- issue permissions
- allowed transitions
- project roles
```

The JWT establishes identity. The Symfony backend remains authoritative for authorization and business rules.

### Refresh tokens

Refresh tokens are opaque random tokens.

Rules:

```text
- refresh tokens are not JWTs
- refresh tokens are stored in HttpOnly Secure cookies
- refresh tokens are never returned in JSON
- only refresh token hashes are stored in the database
- refresh tokens have an initial lifetime of 30 days
- refresh tokens rotate on every successful refresh
```

Initial refresh cookie direction:

```text
Name: refresh_token
Host: api.boardly.com
Domain: not set
Path: /api/auth
HttpOnly: true
Secure: true
SameSite: Lax initially
```

The cookie is host-only. Boardly must not set `Domain=.boardly.com` unless a future requirement explicitly justifies sharing the cookie across subdomains.

If browser behavior for `boardly.com -> api.boardly.com` requires it, `SameSite=None; Secure` may be adopted after implementation verification.

### Refresh token rotation and reuse detection

On every successful refresh:

```text
- validate the current refresh token
- invalidate/replace the current refresh token
- create a new opaque refresh token
- store only the hash of the new token
- set a new refresh_token cookie
- return a new 15-minute JWT access token
```

If refresh token reuse is detected:

```text
- reject the refresh request
- revoke the whole token family/session chain
- clear the refresh_token cookie where possible
- require re-login
```

Public response for refresh-token failures must stay generic:

```text
401 Unauthorized
error code: invalid_refresh_token
```

Applies to:

```text
- missing token
- expired token
- revoked token
- invalid token
- unknown token
- reused token
```

### Frontend token storage

The Next.js frontend must store the JWT access token only in memory.

Forbidden storage locations:

```text
- localStorage
- sessionStorage
- IndexedDB
- JavaScript-readable cookies
```

After a browser reload, the frontend should call:

```text
POST /api/auth/refresh
```

The refresh request uses the HttpOnly refresh cookie and returns a new access token if the refresh session is still valid.

### Account lifecycle

The IdentityAccess domain uses `Account` as the primary domain entity name.

Initial account statuses:

```text
pending_approval
active
rejected
disabled
```

Meaning:

```text
pending_approval = registered but waiting for admin approval
active           = approved and login is allowed
rejected         = registration/request was rejected and login is denied
disabled         = previously active account was disabled and login is denied
```

Only `active` accounts can receive JWT access tokens and refresh cookies.

Registration behavior:

```text
POST /api/auth/register:
- creates Account
- sets status to pending_approval
- returns 201 Created
- does not issue JWT access token
- does not create refresh token/session
- does not set refresh_token cookie
```

Initial registration response:

```json
{
  "id": "account-id",
  "email": "user@example.com",
  "name": "Alex",
  "status": "pending_approval"
}
```

Login behavior:

```text
POST /api/auth/login:
- validates credentials
- checks account status
- denies login if account is not active
- issues JWT access token and refresh_token cookie only for active accounts
```

If credentials are valid but the account is not active:

```text
403 Forbidden
error code: account_not_active
```

If credentials are invalid:

```text
401 Unauthorized
error code: invalid_credentials
```

Do not reveal whether the email exists or whether the password was wrong.

### Admin approval

Only system admins can approve or reject accounts in the first milestone.

Initial admin approval API direction:

```text
GET  /api/admin/accounts/pending
POST /api/admin/accounts/{accountId}/approve
POST /api/admin/accounts/{accountId}/reject
```

Access rule:

```text
system admin only
```

Behavior:

```text
pending_approval -> active
pending_approval -> rejected
```

The first system admin is created via CLI command, not public registration.

Initial command direction:

```bash
php bin/console boardly:identity:create-system-admin admin@example.com
```

Boardly must not implement temporary logic such as:

```text
first registered user becomes admin
```

### Logout

Logout is idempotent.

```text
POST /api/auth/logout
```

Behavior:

```text
If refresh_token cookie exists and maps to a valid active refresh token/session:
- revoke the current refresh token/session
- clear the refresh_token cookie
- return 204 No Content

If refresh_token cookie is missing, expired, already revoked, invalid, or unknown:
- clear the refresh_token cookie anyway
- return 204 No Content
```

The public response must not reveal whether a refresh token existed or was valid.

### /api/auth/me

```text
GET /api/auth/me
```

Requires:

```http
Authorization: Bearer <accessToken>
```

Initial response:

```json
{
  "id": "account-id",
  "email": "admin@example.com",
  "name": "Admin"
}
```

This endpoint returns only basic authenticated identity/profile information.

It must not return:

```text
- project permissions
- workflow permissions
- issue permissions
- allowed transitions
- role matrices
```

### CORS

Because the frontend and API are separate origins, production CORS must be explicit.

Initial production CORS direction:

```text
Allowed origin: https://boardly.com
Allow credentials: true
Allowed methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Allowed headers: Authorization, Content-Type, X-CSRF-Intent
```

Boardly must not use wildcard CORS with credentials.

### CSRF baseline

Because refresh/logout use a cookie, these endpoints must not rely only on the browser-attached cookie.

Initial CSRF baseline for cookie-based auth endpoints:

```text
- SameSite=Lax refresh cookie initially
- require a custom request header for refresh/logout
```

Affected endpoints:

```text
POST /api/auth/refresh
POST /api/auth/logout
```

Initial header direction:

```http
X-CSRF-Intent: auth-refresh
```

A stronger CSRF token mechanism may be added later if deployment topology requires it.

### Password hashing

Boardly will use Symfony PasswordHasher with `algorithm=auto`.

Rules:

```text
- do not hash passwords manually
- do not store plain-text passwords
- do not use md5, sha1, or raw sha256 for passwords
- do not implement custom password verification in controllers
```

Symfony PasswordHasher is an infrastructure detail. The IdentityAccess domain model must not depend directly on Symfony components.

### Login rate limiting

Login attempts must be rate-limited by IP + email.

Initial conceptual limit:

```text
5 failed attempts per 5 minutes per IP+email
```

After the limit is exceeded:

```text
429 Too Many Requests
error code: too_many_login_attempts
```

The exact counter storage can be decided during implementation. Redis is a likely candidate, but the application boundary should remain explicit.

### Password reset

Public self-service password reset is not part of the first authentication milestone.

Rejected for the first milestone:

```text
POST /api/auth/forgot-password
POST /api/auth/reset-password
```

Password reset should initially be admin-controlled and designed later as a separate issue.

Possible future admin route direction:

```text
POST /api/admin/accounts/{accountId}/reset-password
```

## Source structure impact

IdentityAccess owns authentication and account lifecycle behavior:

```text
src/Boardly/IdentityAccess/
```

Initial concepts:

```text
Account
AccountId
AccountStatus
RefreshToken / AuthSession
```

Infrastructure/interface adapter concerns include:

```text
- Symfony Security integration
- JWT encoding/signing/verification
- cookie creation/clearing
- HTTP request/response DTOs
- CORS/CSRF header handling
- PasswordHasher adapter
- persistence mappings
```

The IdentityAccess domain must not depend on:

```text
- Symfony Security
- JWT libraries
- HTTP cookies
- Doctrine
- controllers
```

## Consequences

Positive consequences:

```text
- short-lived access tokens reduce risk from leaked access tokens
- refresh tokens are not readable by frontend JavaScript
- refresh token rotation gives reuse detection
- frontend reload is supported through refresh endpoint
- admin approval supports controlled access to the product
- IdentityAccess has a clear domain boundary
```

Costs and trade-offs:

```text
- refresh token persistence is required
- token family/session tracking is required
- CORS and cookie behavior must be tested carefully
- frontend must implement refresh-on-load and refresh-on-401 behavior
- admin approval requires admin API/UI or CLI-supported operational flow
```

## Alternatives considered

### Server-side session cookie only

Rejected for now.

Reason:

```text
Boardly is API-first with explicit frontend/backend separation.
JWT access tokens keep API authentication explicit while refresh cookies handle browser session continuity.
```

### Long-lived JWT access token

Rejected.

Reason:

```text
A long-lived bearer token increases impact if stolen and makes revocation harder.
```

### JWT refresh token

Rejected.

Reason:

```text
Refresh tokens must be opaque and server-controlled so they can be rotated, hashed, revoked, and checked for reuse.
```

### Store tokens in localStorage

Rejected.

Reason:

```text
localStorage is readable by JavaScript and increases impact from XSS.
```

### Public registration automatically logs in the user

Rejected.

Reason:

```text
Registration creates an account request. Admin approval activates the account. Login creates an authenticated session.
```

### First registered user becomes system admin

Rejected.

Reason:

```text
This creates unsafe bootstrap behavior and can accidentally grant privileged access through a public endpoint.
```

## Related documents

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/architecture/project-architecture-rules.md
```
