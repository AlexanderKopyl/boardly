# Issue #50 Analysis: Add GET /api/auth/me Endpoint

Branch: `feature/50-add-get-apiauthme-endpoint-for-current-authenticated-account`
Scope: analysis only — no implementation

---

## 1. Issue Summary

Issue #50 adds `GET /api/auth/me`.

The endpoint must return minimal safe identity data for the currently authenticated account using the `Authorization: Bearer <accessToken>` JWT access token. It is a frontend session-bootstrap endpoint only. It must not introduce project/task/workflow permissions, broad RBAC, admin permission matrices, token internals, Doctrine entities, or domain aggregate exposure.

Expected flow:

```text
HTTP Controller
-> Application query/use case
-> AccountRepositoryInterface
-> Response mapping
```

Preferred source shape from the issue:

```text
src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php
src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandler.php
src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountResult.php
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php
```

The controller should stay thin. It should read the authenticated Symfony principal, map only the account id into an application query, send it through the existing query bus, and map the application result into JSON.

---

## 2. MemPalace Result Summary

Query used:

```text
IdentityAccess authentication JWT bearer authenticator current authenticated account security principal application query AccountRepositoryInterface API error mapping OpenAPI HTTP security tests
```

Relevant memory:

- Boardly uses `Authorization: Bearer <jwt>` for protected API requests.
- JWT `sub` maps to `AccountId`; protected requests load the account through `AccountRepositoryInterface`.
- Missing, invalid, expired, unknown-account, and non-active-account tokens should receive generic JSON `401`.
- Login creates short-lived JWT access tokens and an opaque HttpOnly refresh-token cookie.
- Refresh rotates refresh tokens and returns a new access token.
- Logout revokes/clears refresh session state and does not reveal token validity.
- Authorization/RBAC was intentionally out of scope for the auth baseline.
- HTTP/security tests should assert sensitive data is not exposed.
- Prior OpenAPI analysis said `GET /api/auth/me` was planned but not implemented/documented yet.

Conflicts with current repository evidence:

- No material conflict. Current code confirms the bearer authenticator, generic 401 response, active-account check, and missing `/api/auth/me` implementation.
- Current code has evolved from early docs on refresh cookie SameSite: code/tests use `SameSite=None`, while ADR-0005 initially described `Lax` with a possible switch. Current code is stronger evidence.

---

## 3. Files Inspected and Why

| File | Why it matters |
|---|---|
| `AGENTS.md` | Boardly task process, security constraints, DDD/Hexagonal rules, source-of-truth rules |
| `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md` | Confirms controller/application/domain boundaries and source-of-truth rules |
| `docs/adr/0002-use-boardly-context-based-source-structure.md` | Confirms IdentityAccess placement under `src/Boardly/IdentityAccess/{Application,Domain,Infrastructure,Interfaces}` |
| `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` | Confirms API-first backend and thin controller responsibility |
| `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` | Auth contract, `/api/auth/me` direction, safe response, forbidden permission data |
| `docs/design/authentication-api-strategy.md` | Route contract for `/api/auth/me`, frontend bootstrap direction, test direction |
| `docs/design/account-domain-model.md` | Account aggregate scope and explicit non-ownership of project/workflow/issue permissions |
| `docs/architecture/http-controller-rules.md` | Controller responsibilities, forbidden controller logic, centralized exception mapping expectations |
| `docs/architecture/application-bus-rules.md` | Command/query bus conventions and `GetCurrentAccount` as a query example |
| `docs/architecture/project-architecture-rules.md` | CQRS rules and read/query guidance |
| `docs/tasks/issues-14/analysis.md` | Existing analysis artifact style and controller/use-case convention |
| `docs/tasks/issues-46/analysis.md` | Current OpenAPI/auth endpoint inventory and previous `/api/auth/me` status |
| `docs/tasks/issues-49/analysis.md` | Frontend dependency/risk around missing `/api/auth/me` |
| `config/routes.yaml` | Confirms IdentityAccess controller attribute scanning is already configured |
| `config/packages/security.yaml` | Confirms `/api/auth/me` will match protected `^/api` access control unless explicitly made public |
| `config/packages/messenger.yaml` | Confirms `query.bus` exists |
| `config/services.yaml` | Confirms query bus alias, handler tags for existing commands, OpenAPI schema describer registration |
| `config/packages/nelmio_api_doc.yaml` | Confirms OpenAPI bearer auth scheme and `/api` path scanning |
| `config/routes/dev/nelmio_api_doc.yaml` | Confirms dev OpenAPI routes exist |
| `config/routes/test/boardly_security_test.yaml` | Shows current test-only protected route used for bearer authentication verification |
| `src/Shared/Application/Bus/QueryBusInterface.php` | Query entry point contract: `ask(object $query): mixed` |
| `src/Shared/Infrastructure/Symfony/Messenger/MessengerQueryBus.php` | Query bus adapter unwraps single `HandlerFailedException` failures |
| `src/Boardly/IdentityAccess/Infrastructure/Security/BearerTokenAuthenticator.php` | Bearer token auth flow, account loading, active-account check, principal creation |
| `src/Boardly/IdentityAccess/Infrastructure/Security/AuthenticatedAccountUser.php` | Symfony security principal fields available to controllers |
| `src/Boardly/IdentityAccess/Infrastructure/Security/JwtAccessTokenService.php` | JWT claims and verification behavior |
| `src/Boardly/IdentityAccess/Infrastructure/Security/AuthenticationFailureResponseFactory.php` | Current generic 401 JSON shape |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginController.php` | Current controller style, OpenAPI attributes, command bus usage, login account response |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php` | Refresh flow, CSRF header behavior, cookie response conventions |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LogoutController.php` | Logout flow and inline CSRF error response |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Thin controller pattern and response mapping |
| `src/Boardly/IdentityAccess/Interfaces/Http/EventSubscriber/IdentityAccessApiExceptionSubscriber.php` | Centralized API exception subscriber scope |
| `src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php` | Repository capabilities: `get`, `find`, `findByEmail`, `existsByEmail`, `countActiveSystemAdmins` |
| `src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Repository/DoctrineAccountRepository.php` | Current implementation of `get/find` and missing-account behavior |
| `src/Boardly/IdentityAccess/Application/Exception/AccountNotFound.php` | Missing account exception type and generic message |
| `src/Boardly/IdentityAccess/Domain/Model/Account.php` | Available account fields and sensitive fields that must not be returned |
| `src/Boardly/IdentityAccess/Domain/ValueObject/AccountStatus.php` | Valid account statuses and `isActive()` behavior |
| `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticatedAccount.php` | Existing safe account DTO shape for login |
| `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountResult.php` | Existing result object style and safe account sub-object usage |
| `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/SchemaDescriber.php` | Existing OpenAPI schema registration mechanism |
| `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/LoginAccountData.php` | Existing schema for safe account data: `id`, `email`, `name`, `status` |
| `tests/Boardly/IdentityAccess/Infrastructure/Security/BearerTokenAuthenticatorTest.php` | Unit test coverage for authenticator and principal fields |
| `tests/Boardly/IdentityAccess/Interfaces/Http/Security/ApiAuthenticationTest.php` | Functional security test conventions for protected API routes |
| `tests/Boardly/IdentityAccess/Interfaces/Http/Security/TestProtectedApiController.php` | Shows how controllers can read `AuthenticatedAccountUser` from token storage |
| `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginControllerTest.php` | HTTP test style and safe response assertions |
| `tests/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountHandlerTest.php` | Application test style using fakes and safe DTO assertions |

Search facts:

- No source implementation currently exists for `GetCurrentAccount`, `api_auth_me`, or `/api/auth/me`.
- `docs/tasks/issues-50/` did not exist before this analysis step.

---

## 4. Existing Auth Flow

### Login

Implemented by `LoginController` and `AuthenticateAccountHandler`.

Flow:

```text
POST /api/auth/login
-> LoginRequestDto via #[MapRequestPayload]
-> AuthenticateAccountCommand through CommandBusInterface
-> AuthenticateAccountHandler
-> access token JSON + refresh_token HttpOnly cookie
```

Successful login response currently includes:

```json
{
  "accessToken": "<jwt>",
  "tokenType": "Bearer",
  "expiresIn": 900,
  "account": {
    "id": "<uuid>",
    "email": "user@example.com",
    "name": "User Name",
    "status": "active"
  }
}
```

The refresh token is set only as a cookie and is not returned in JSON. Current code uses cookie name `refresh_token`, path `/api/auth`, `HttpOnly`, `Secure`, and `SameSite=None`.

### Refresh

Implemented by `RefreshAuthenticationController` and `RefreshAuthenticationHandler`.

Flow:

```text
POST /api/auth/refresh
-> requires X-CSRF-Intent: auth-refresh
-> reads refresh_token cookie
-> RefreshAuthenticationCommand through CommandBusInterface
-> rotates refresh session
-> returns new access token JSON + rotated refresh_token cookie
```

Success response:

```json
{
  "accessToken": "<jwt>",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

Refresh failure maps to `401 invalid_refresh_token` through `RefreshTokenExceptionMapper`, except CSRF header failure is handled inline by the controller as `403 csrf_intent_required` and clears the cookie.

### Logout

Implemented by `LogoutController` and `LogoutHandler`.

Flow:

```text
POST /api/auth/logout
-> requires X-CSRF-Intent: auth-refresh
-> LogoutCommand through CommandBusInterface
-> clears refresh_token cookie
-> returns 204
```

Logout is idempotent. Missing or invalid refresh-token state is not revealed.

### Bearer Token Authentication

Implemented by `BearerTokenAuthenticator`.

Flow:

```text
Authorization: Bearer <accessToken>
-> JwtAccessTokenService::verify()
-> VerifiedAccessToken(accountId, jti, issuedAt, expiresAt)
-> AccountRepositoryInterface::find(accountId)
-> require account exists and status is active
-> AuthenticatedAccountUser::fromAccount(account)
```

JWT claims currently issued/required by `JwtAccessTokenService`:

```text
sub = account id
iat = issued at
exp = expires at
jti = token id
```

JWT does not include roles, project permissions, workflow permissions, issue permissions, allowed transitions, or system-admin state.

---

## 5. Current Security Principal / Authenticated Identity

`BearerTokenAuthenticator` exposes identity through Symfony Security as `AuthenticatedAccountUser`.

Available principal class:

```text
App\Boardly\IdentityAccess\Infrastructure\Security\AuthenticatedAccountUser
```

Fields available on the principal:

```text
accountId(): AccountId
email(): string
name(): string
status(): string
getUserIdentifier(): string
getRoles(): list<string>
```

`AuthenticatedAccountUser::fromAccount()` currently maps:

```text
id     <- Account::id()
email  <- Account::email()
name   <- Account::name()
status <- Account::status()
roles  <- ['ROLE_USER']
```

Safe fields to use from the principal:

- `accountId()` is safe and should be enough for the controller to create `GetCurrentAccountQuery`.
- `email()`, `name()`, and `status()` are safe identity/profile fields, but the preferred architecture still routes account lookup through the application query/use case.
- `getRoles()` currently returns only `ROLE_USER`; it should not be returned by `/api/auth/me` because the endpoint is not a permission or role-discovery API.

Not available on the principal:

- `isSystemAdmin()`
- password hash
- refresh session/token data
- JWT claims (`jti`, `iat`, `exp`)
- permission matrix

Implementation note:

- Existing test-only controller reads the principal through `TokenStorageInterface`.
- A production controller can do the same or use Symfony’s security helper, but it must guard the type and should not perform account lookup or business logic locally.

---

## 6. Existing Application Query/Handler Conventions

Current IdentityAccess use cases are mostly command-oriented:

```text
RegisterAccount
AuthenticateAccount
RefreshAuthentication
Logout
CreateSystemAdmin
ApproveAccount
```

Current controllers use `CommandBusInterface`, not concrete handlers. `application-bus-rules.md` defines a separate `QueryBusInterface` for reads and lists `GetCurrentAccount` as a query example.

Existing query infrastructure:

```text
src/Shared/Application/Bus/QueryBusInterface.php
src/Shared/Infrastructure/Symfony/Messenger/MessengerQueryBus.php
config/packages/messenger.yaml -> query.bus
config/services.yaml -> QueryBusInterface alias
```

Expected issue #50 convention:

```text
GetCurrentAccountController
-> QueryBusInterface::ask(new GetCurrentAccountQuery($accountId))
-> GetCurrentAccountHandler
-> GetCurrentAccountResult
-> JsonResponse
```

Because query handlers are not currently tagged in `services.yaml`, implementation likely needs to add a handler tag:

```yaml
App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountHandler:
    tags:
        - { name: messenger.message_handler, bus: query.bus }
```

That is an implementation detail for the next step, not part of this analysis-only change.

---

## 7. Existing Account Repository Capabilities

`AccountRepositoryInterface` supports:

```php
public function get(AccountId $id): Account;
public function find(AccountId $id): ?Account;
public function findByEmail(Email $email): ?Account;
public function existsByEmail(Email $email): bool;
public function countActiveSystemAdmins(): int;
public function save(Account $account): void;
```

### Load by Account Id

Both `get(AccountId $id)` and `find(AccountId $id)` are available.

`DoctrineAccountRepository::find()`:

- loads `AccountEntity` by primary key;
- returns `null` when missing;
- maps entity to domain `Account` when present.

`DoctrineAccountRepository::get()`:

- delegates to `find()`;
- throws `AccountNotFound::withId($id)` when missing.

### Missing Account Behavior

`AccountNotFound` has a generic message:

```text
Account was not found.
```

For `/api/auth/me`, missing account after a valid security principal is unusual because the bearer authenticator already loads the account and rejects missing accounts before the controller runs. Still, the application handler should define behavior for missing account id. Preferred behavior is to throw `AccountNotFound` or a small application exception and map it consistently. Because the route is protected, missing-current-account is probably best treated as `401 unauthorized` or a generic `404` only if the existing error policy explicitly supports it. Current code has no mapper for `AccountNotFound`, so this is a risk to resolve during implementation.

### Account Status / Active-Account Checks

`BearerTokenAuthenticator` already enforces:

```php
if (null === $account || !$account->status()->isActive()) {
    throw authenticationFailed();
}
```

Therefore `/api/auth/me` receives only an authenticated active account under normal flow.

The application query may still re-check that the loaded account is active if it reloads from the repository. If it does, non-active/missing should not leak account lifecycle state. Current protected-route behavior returns generic `401 unauthorized` before controller execution for non-active accounts.

---

## 8. Existing API Error Format

### Current 401 JSON Shape

Bearer authentication failures use `AuthenticationFailureResponseFactory`:

```json
{
  "error": {
    "code": "unauthorized",
    "message": "Authentication required."
  }
}
```

Covered failure cases:

- no `Authorization` header on protected API route;
- malformed bearer token;
- invalid token;
- expired token;
- token for missing account;
- token for disabled/non-active account.

Tests assert the response does not expose token claims, failure reasons, account id, or lifecycle status details.

### Where Auth Failures Are Mapped

Protected-route authentication failures are mapped by:

```text
BearerTokenAuthenticator::onAuthenticationFailure()
BearerTokenAuthenticator::start()
AuthenticationFailureResponseFactory
```

Application/domain HTTP errors for `/api/` routes are mapped by:

```text
IdentityAccessApiExceptionSubscriber
-> tagged ApiExceptionMapperInterface implementations
```

Current mappers include:

- `BadRequestExceptionMapper`
- `ValidationExceptionMapper`
- `RegistrationExceptionMapper`
- `AuthenticationExceptionMapper`
- `RefreshTokenExceptionMapper`

CSRF failures for refresh/logout are currently returned inline by those controllers, not through the subscriber.

### Authenticator vs Exception Subscriber

For `/api/auth/me`:

- Missing/invalid/expired token should be handled by the authenticator/entry point before controller execution.
- Application query failures after successful authentication would go through `IdentityAccessApiExceptionSubscriber` only if an existing mapper supports the exception.
- There is no current mapper for `AccountNotFound`, so implementation must avoid uncaught internal errors for a missing reloaded account.

---

## 9. Routing and Security Configuration Facts

### Controller Route Style

IdentityAccess auth controllers use:

- `final readonly class`
- constructor injection
- `#[Route(..., methods: [...], format: 'json')]`
- OpenAPI attributes on controllers
- `JsonResponse` / `Response`
- bus abstraction injection (`CommandBusInterface` today; `QueryBusInterface` expected for `/me`)
- route names like `api_auth_login`, `api_auth_refresh`, `api_auth_logout`, `api_auth_register`

Expected route name:

```text
api_auth_me
```

or:

```text
api_auth_current_account
```

Prefer existing short auth naming style: `api_auth_me`.

### Route Scanning

`config/routes.yaml` already scans:

```text
../src/Boardly/IdentityAccess/Interfaces/Http/Controller/
namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller
```

No new route scan is needed for `GetCurrentAccountController` if it is placed under:

```text
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/
```

### Security Access Control

`config/packages/security.yaml` currently marks these public:

```text
GET  /api/doc and /api/doc.json
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
POST /api/auth/register
```

Then it applies:

```yaml
- { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

Because `/api/auth/me` is not listed as public, it will be protected by the catch-all `^/api` rule. That matches the issue goal.

No `access_control` change should be needed unless implementation accidentally adds a broader `/api/auth` public rule. Do not make `/api/auth/me` public.

---

## 10. Safe Response Data Decision

Recommended response:

```json
{
  "id": "account-id",
  "email": "admin@example.com",
  "name": "Admin",
  "status": "active"
}
```

Field decision:

| Field | Decision | Reason |
|---|---|---|
| `id` | Include | Required identity key for frontend session bootstrap; already safe in login account object |
| `email` | Include | Basic identity/profile data; already safe in login account object |
| `name` | Include | Basic profile/display data; included in ADR/design docs |
| `status` | Include | Already safely exposed in login account object; useful for frontend session state and safe because protected auth only admits active accounts |
| `isSystemAdmin` | Do not include for issue #50 unless product explicitly requires it | The field exists on `Account` and CLI result, but is not exposed by `AuthenticatedAccountUser`, login response, JWT, or current OpenAPI account schema. Including it would start frontend admin-capability behavior and should be a separate authorization/admin contract decision. |

Implementation should use an application result/read model, not return `Account`, `AccountEntity`, or `AuthenticatedAccountUser` directly.

---

## 11. Explicitly Forbidden Response Data

`GET /api/auth/me` must not return:

- password hash
- plain password
- refresh token
- refresh token hash
- access token
- JWT `sub`, `iat`, `exp`, or `jti`
- raw `Authorization` header
- Doctrine entity
- domain aggregate
- Symfony security token object
- `AuthenticatedAccountUser` object serialization
- refresh session ids/family ids
- project memberships
- project/task/workflow/admin permission matrix
- project roles
- issue permissions
- workflow permissions
- allowed transitions
- global role lists such as `ROLE_USER` unless a separate role contract is accepted
- `isSystemAdmin` unless separately justified and explicitly accepted for this endpoint

---

## 12. OpenAPI Status

OpenAPI support exists.

Current facts:

- `nelmio/api-doc-bundle` is present according to issue #46 analysis.
- `config/routes/dev/nelmio_api_doc.yaml` exposes `/api/doc` and `/api/doc.json` in dev routes.
- `config/packages/nelmio_api_doc.yaml` defines OpenAPI 3.1 metadata and a `bearerAuth` HTTP bearer JWT scheme.
- Existing auth controllers already have OpenAPI attributes.
- `SchemaDescriber` scans `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/` and registers standalone schema classes.
- Existing schemas include `LoginAccountData`, `AccessTokenResponse`, `LoginResponse`, and error envelopes.

Issue #46 work exists in the repo:

- `docs/tasks/issues-46/analysis.md`
- `docs/tasks/issues-46/plan.md`
- OpenAPI schema classes under `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/`
- OpenAPI attributes on auth controllers
- Nelmio config and dev routes

Current `/api/auth/me` OpenAPI status:

- Not implemented in source.
- Not documented as an active endpoint.
- Issue #46 analysis explicitly excluded `/api/auth/me` because it was not implemented.

Decision for issue #50:

- Because the current controller convention already includes OpenAPI attributes and issue #46 added the OpenAPI foundation, the implementation should likely add OpenAPI docs with the controller in the same issue.
- If the allowed implementation scope later excludes OpenAPI docs, defer documentation explicitly and record the contract gap. For this analysis-only step, no OpenAPI files are modified.

---

## 13. Test Conventions

### Application Tests

Use PHPUnit `TestCase` with inline fakes, matching existing application tests.

Recommended `GetCurrentAccountHandlerTest` cases:

- returns `GetCurrentAccountResult` with `id`, `email`, `name`, `status` for an active account id;
- uses `AccountRepositoryInterface` by account id;
- missing account behavior is explicit and mapped or documented;
- non-active account behavior is explicit if handler re-checks active status;
- result public API exposes only safe fields and does not expose account aggregate/entity/password/token methods.

### HTTP/Security Tests

Use `WebTestCase`, matching `ApiAuthenticationTest` and auth controller tests.

Recommended `GetCurrentAccountControllerTest` cases:

- `GET /api/auth/me` without Authorization returns `401` with:

```json
{
  "error": {
    "code": "unauthorized",
    "message": "Authentication required."
  }
}
```

- malformed/expired token returns the same generic `401`;
- token for missing account returns generic `401` before controller response;
- token for disabled/non-active account returns generic `401`;
- valid token for active account returns `200` and only safe identity keys;
- response contains `id`, `email`, `name`, `status`;
- response does not contain `password`, `plainPassword`, `passwordHash`, `refreshToken`, `refreshTokenHash`, `accessToken`, `sub`, `iat`, `exp`, `jti`, `roles`, `permissions`, `projects`, `workflows`, `allowedTransitions`, `isSystemAdmin` unless explicitly accepted.

Test setup can reuse the existing pattern:

- set test secrets in `setUp()`;
- boot `KernelBrowser`;
- use `EntityManagerInterface`, `AccountRepositoryInterface`, `PasswordHasherInterface`, and `AccessTokenIssuerInterface`;
- clear `refresh_sessions` and `accounts`;
- persist active/non-active accounts through the repository and flush through EntityManager in tests.

### Safe Response Assertions

Follow existing login and security tests:

- assert exact top-level keys when possible;
- assert absent sensitive keys in decoded JSON;
- assert raw response body does not contain sensitive substrings;
- assert 401 responses do not leak token failure reasons or claims.

---

## 14. Risks, Blockers, and Unclear Areas

1. **Missing account after successful authentication lacks mapper.** The authenticator should prevent this, but a query handler that reloads the account can still encounter a missing account. Current `AccountNotFound` has no API mapper. Implementation must choose a controlled behavior instead of allowing a 500.

2. **`isSystemAdmin` is tempting but should stay out.** The domain has `isSystemAdmin`, but current principal and login account response do not expose it. Returning it from `/me` would create a frontend capability contract and blur this bootstrap endpoint into authorization/admin discovery.

3. **Query handler must be registered on `query.bus`.** Existing command handlers are manually tagged in `services.yaml`. A new query handler likely needs an explicit `messenger.message_handler` tag for `query.bus`.

4. **Controller principal access must be type-safe.** Protected access control should ensure a user exists, but the controller should still guard that the user is `AuthenticatedAccountUser` and avoid serializing arbitrary Symfony users.

5. **OpenAPI scope should be decided in implementation.** Current auth controllers carry OpenAPI attributes. Adding `/api/auth/me` without docs would create a contract gap, but the user explicitly forbade modifying OpenAPI docs in this analysis step.

6. **Refresh response still lacks account payload.** `/api/auth/me` is useful for frontend bootstrap after refresh because refresh returns only access token data. This endpoint should remain identity-only and not become a broader session/permissions endpoint.

7. **Current `git status` could not run in this environment.** The command failed due a missing local Xcode command-line tools path (`xcrun` error). File-scope verification for this analysis used targeted filesystem inspection instead.

---

## 15. Analysis-Only Verification

No production source, test, config, migration, OpenAPI, composer, frontend, or `docs/tasks/issues-50/plan.md` files were modified in this step.

Created:

```text
docs/tasks/issues-50/analysis.md
```

Next expected artifact:

```text
docs/tasks/issues-50/plan.md
```

Only a later planning step should create that file.
