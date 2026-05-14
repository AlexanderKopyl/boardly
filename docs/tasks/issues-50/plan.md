# Issue #50 Implementation Plan: GET /api/auth/me Current Account Endpoint

> **For agentic workers:** REQUIRED SUB-SKILL: Use `task-implementation` or task-by-task execution. Steps use checkbox (`- [ ]`) syntax for tracking. Implement only after this plan is accepted or selected for implementation.

**Goal:** Add `GET /api/auth/me`, a protected endpoint that returns minimal safe identity data for the currently authenticated account using the `Authorization: Bearer <accessToken>` JWT access token.

**Architecture:** Thin HTTP controller in IdentityAccess `Interfaces` layer -> application query/use case -> `AccountRepositoryInterface` -> response mapping. The controller reads the authenticated principal, creates a query from the account id only, asks the query bus, and maps the application result into JSON.

**Tech Stack / Existing Conventions:** PHP 8.2+, Symfony 7-style attribute routes, `final readonly` controllers, `JsonResponse`, Messenger-backed `QueryBusInterface::ask()`, IdentityAccess routes under `src/Boardly/IdentityAccess/Interfaces/Http/Controller/`, current principal type `AuthenticatedAccountUser`, centralized generic auth failure response from `AuthenticationFailureResponseFactory`, context-local API exception mapping through `IdentityAccessApiExceptionSubscriber`, and PHPUnit `TestCase`/`WebTestCase` tests with focused application and HTTP/security coverage.

**Current error envelope for auth failures:**

```json
{
  "error": {
    "code": "unauthorized",
    "message": "Authentication required."
  }
}
```

---

## Scope

Implement only the current-account identity bootstrap endpoint:

```http
GET /api/auth/me
Authorization: Bearer <accessToken>
```

Successful response:

```json
{
  "id": "...",
  "email": "...",
  "name": "...",
  "status": "..."
}
```

Do not include `isSystemAdmin` in issue #50. `analysis.md` confirms the domain model has the value, but the current principal, login response, JWT, and OpenAPI account schema do not expose it as a safe frontend bootstrap contract.

---

## Non-goals

- Do not introduce broad RBAC, project roles, permission matrices, workflow permissions, issue permissions, or allowed transitions.
- Do not expose JWT claims or refresh-token internals.
- Do not return Doctrine entities, domain aggregates, Symfony security token objects, or serialized principals.
- Do not make `/api/auth/me` public.
- Do not move business/query behavior into the controller.
- Do not change frontend code as part of this issue.

---

## Relevant Context

- `docs/tasks/issues-50/analysis.md` is the source analysis artifact for this plan.
- MemPalace confirmed the existing authentication direction: bearer JWT for protected API requests, JWT `sub` as `AccountId`, account lookup through `AccountRepositoryInterface`, generic JSON `401` for missing/invalid/expired/unknown/non-active tokens, refresh token in HttpOnly cookie, RBAC out of scope, and prior OpenAPI work excluding `/api/auth/me` because it was not implemented yet.
- Current repository evidence is stronger than memory. No material conflict was found for issue #50.
- `BearerTokenAuthenticator` already verifies JWTs, loads accounts, rejects missing/non-active accounts, and creates `AuthenticatedAccountUser`.
- `security.yaml` already protects `/api/auth/me` through the existing `^/api` `IS_AUTHENTICATED_FULLY` rule because `/api/auth/me` is not listed as public.
- Route scanning already covers `src/Boardly/IdentityAccess/Interfaces/Http/Controller/`.
- Query infrastructure exists through `QueryBusInterface`, `MessengerQueryBus`, and `query.bus`, but the new query handler likely needs an explicit `messenger.message_handler` tag for `query.bus`.

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php` | Application query carrying the authenticated account id from the security principal |
| Create | `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandler.php` | Loads current account through `AccountRepositoryInterface`, applies current account-state convention, returns a safe result |
| Create | `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountResult.php` | Safe application read model with only `id`, `email`, `name`, `status` |
| Create | `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php` | Thin protected controller for `GET /api/auth/me`; extracts `AuthenticatedAccountUser`, asks query bus, maps result to `JsonResponse` |
| Modify | `config/services.yaml` | Register `GetCurrentAccountHandler` on `query.bus` if autoconfiguration does not already do this |
| Create | `tests/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandlerTest.php` | Application tests for active account, missing account, and non-active handling if checked in the use case |
| Create | `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountControllerTest.php` | HTTP/security tests for auth failures, valid response, and forbidden sensitive fields |
| Modify | `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/*` | Add a `CurrentAccountResponse` or reuse safe account schema only if needed for accurate OpenAPI docs |
| Modify | `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php` | Add OpenAPI attributes on the new controller because issue #46 OpenAPI structure exists and current auth controllers use attributes |

OpenAPI documentation should be updated during implementation unless the implementation scope explicitly excludes OpenAPI files. If excluded, record a deferred follow-up linked to issue #46 because the route would otherwise create a contract gap.

---

## Step-by-step Implementation Plan

- [ ] Step 1: Confirm the working tree and baseline IdentityAccess tests before coding; stop if unrelated failures obscure issue #50.
- [ ] Step 2: Add `GetCurrentAccountQuery` with an `AccountId` input created from the authenticated principal/account id.
- [ ] Step 3: Add `GetCurrentAccountResult` exposing only `id`, `email`, `name`, and `status` as strings.
- [ ] Step 4: Add `GetCurrentAccountHandler` that loads the account through `AccountRepositoryInterface` by id and maps the account into `GetCurrentAccountResult`.
- [ ] Step 5: Define controlled missing-account and non-active-account behavior in the handler using the current standardized auth failure policy; do not allow an uncaught `AccountNotFound` to become a 500.
- [ ] Step 6: Register the query handler on `query.bus` if existing Symfony Messenger configuration does not autowire it automatically.
- [ ] Step 7: Add `GetCurrentAccountController` under `Interfaces/Http/Controller/Auth` with `#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'], format: 'json')]`.
- [ ] Step 8: In the controller, obtain the authenticated user through the existing security principal style, guard that it is `AuthenticatedAccountUser`, create `GetCurrentAccountQuery` from `accountId()`, call `QueryBusInterface::ask()`, and map the result to `JsonResponse`.
- [ ] Step 9: Add OpenAPI attributes/schema for `GET /api/auth/me` using the existing issue #46 OpenAPI conventions and `bearerAuth`; document 200 and 401 responses.
- [ ] Step 10: Add application tests for successful active account lookup, missing account controlled failure, and non-active account rejection if the use case performs the active check.
- [ ] Step 11: Add HTTP/security tests for missing bearer token, invalid token, expired token, token for missing account, token for non-active account, valid active account, and sensitive-field absence.
- [ ] Step 12: Run focused syntax, routing, firewall, PHPUnit, OpenAPI/container, and lint checks listed below.
- [ ] Step 13: Update implementation notes with exact behavior decisions, especially missing-account and non-active-account mapping.

---

## Application Layer Plan

### `GetCurrentAccountQuery`

- Input: authenticated `AccountId`.
- Source: `AuthenticatedAccountUser::accountId()` from Symfony Security, not request body or query string.
- No email, status, roles, JWT claims, or token data in the query.

### `GetCurrentAccountHandler`

- Dependency: `AccountRepositoryInterface`.
- Behavior:
  - Load the account by id through the repository.
  - Return a `GetCurrentAccountResult` for an active account.
  - Missing account must map to current standardized auth failure behavior, not a leaked not-found reason or 500.
  - Non-active account handling must follow the existing authenticator/use-case convention: generic authentication failure behavior and no lifecycle-status leak.
- Use the domain/application model only inside the handler. Do not return the aggregate.

### `GetCurrentAccountResult`

- Expose only safe scalar fields:
  - `id`
  - `email`
  - `name`
  - `status`
- Do not expose `isSystemAdmin` for issue #50 because `analysis.md` says it is not currently exposed safely or required for frontend bootstrap.

---

## HTTP Layer Plan

### `GetCurrentAccountController`

- Path: `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php`
- Route:

```php
#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'], format: 'json')]
```

- Dependencies:
  - `QueryBusInterface`
  - existing Symfony security helper or `TokenStorageInterface`, following the current principal extraction style from tests/controllers
- Controller responsibilities:
  - Read the authenticated identity from Symfony Security.
  - Verify the principal is `AuthenticatedAccountUser`.
  - Create `GetCurrentAccountQuery` from `AuthenticatedAccountUser::accountId()`.
  - Invoke `QueryBusInterface::ask($query)`.
  - Map `GetCurrentAccountResult` to `JsonResponse` with status `200`.
- Controller must not:
  - load account from repository;
  - inspect JWT claims;
  - check passwords, refresh sessions, project memberships, or permissions;
  - serialize `AuthenticatedAccountUser`, domain `Account`, or Doctrine entity.

---

## Security/Error Behavior Plan

Use the existing centralized auth error format. Do not invent a second auth error envelope.

| Scenario | Expected behavior |
|----------|-------------------|
| Missing bearer token | `401` with `error.code = unauthorized` and `Authentication required.` |
| Invalid bearer token | `401` with the same generic auth envelope |
| Expired bearer token | `401` with the same generic auth envelope |
| Token for missing account | `401` with the same generic auth envelope |
| Token for non-active account | current standardized auth failure behavior; no status/lifecycle leak |
| Valid token for active account | `200` with safe account identity fields |

Missing/invalid/expired token failures should be handled by the authenticator/entry point before controller execution. Query-layer failures after authentication must still be controlled and must not become an internal server error.

---

## Response Shape Plan

Exact JSON response for a valid active account:

```json
{
  "id": "...",
  "email": "...",
  "name": "...",
  "status": "..."
}
```

`isSystemAdmin` is intentionally excluded for issue #50.

---

## Forbidden Response Data

`GET /api/auth/me` must never return:

- password hash
- plain password
- refresh token
- refresh token hash
- access token
- JWT internals including `sub`, `iat`, `exp`, and `jti`
- raw `Authorization` header
- Doctrine entity
- domain aggregate
- Symfony security token object
- serialized `AuthenticatedAccountUser`
- refresh session ids or family ids
- permission matrix
- project roles
- workflow permissions
- issue permissions
- allowed transitions
- broad role lists such as `ROLE_USER`
- `isSystemAdmin` unless a later issue explicitly accepts it as part of the frontend bootstrap contract

---

## OpenAPI Plan

Current analysis says OpenAPI support exists from issue #46:

- Nelmio API docs are configured.
- `bearerAuth` exists.
- Auth controllers already use OpenAPI attributes.
- Schema classes are registered by `SchemaDescriber`.
- Issue #46 explicitly excluded `/api/auth/me` only because it was not implemented yet.

Plan:

- [ ] Add OpenAPI attributes to `GetCurrentAccountController`.
- [ ] Document `GET /api/auth/me` as bearer-auth protected.
- [ ] Document `200` with the safe current-account response shape.
- [ ] Document `401` using the existing error envelope.
- [ ] Reuse `LoginAccountData` only if the schema name remains semantically acceptable; otherwise create a `CurrentAccountResponse` schema with the exact same safe fields.

If OpenAPI files are excluded from the implementation scope, add an explicit deferred follow-up note linked to issue #46 and record that `/api/auth/me` is implemented but not yet documented.

---

## Test Plan

### Application tests

- [ ] `GetCurrentAccountHandler` returns the current active account by account id.
- [ ] Missing account fails with controlled auth/not-found behavior that maps safely for `/api/auth/me`.
- [ ] Non-active account is rejected if active-state enforcement belongs in the use case in addition to the authenticator.
- [ ] Result exposes only safe fields and no aggregate/entity/password/token accessors.

### HTTP/security tests

- [ ] Missing bearer token returns `401`.
- [ ] Invalid bearer token returns `401`.
- [ ] Expired bearer token returns `401`.
- [ ] Token for missing account returns `401`.
- [ ] Token for non-active account returns `401`.
- [ ] Valid active-account token returns `200`.
- [ ] Response contains exactly the safe account fields: `id`, `email`, `name`, `status`.
- [ ] Response does not contain password/hash/token/JWT/role/permission/project/workflow/allowed-transition data.

---

## Verification Commands

Run focused checks first, then broader checks:

```bash
php -l src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php
php -l src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandler.php
php -l src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountResult.php
php -l src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php
php bin/console debug:router | grep '/api/auth/me'
php bin/console debug:firewall api
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/GetCurrentAccount
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountControllerTest.php
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess
php bin/console lint:container
```

If OpenAPI docs are updated:

```bash
php bin/console debug:router | grep '/api/doc'
php bin/console lint:container
```

---

## Stop Conditions Before Coding

Stop and update the plan or ask for direction before implementation if:

- `analysis.md` is contradicted by current code and authenticated principal/account id is unavailable.
- The repository cannot load account by id through `AccountRepositoryInterface`.
- The standardized `401` format is unclear or cannot be reused for query-layer missing/non-active failures.
- `security.yaml` route protection conflicts with the expected protected behavior for `/api/auth/me`.
- OpenAPI docs structure is absent but current branch scope requires OpenAPI documentation.
- Existing IdentityAccess tests fail before implementation in a way that blocks meaningful verification.
- Adding handler registration requires broad service/container changes outside the IdentityAccess query-handler convention.
- Including `isSystemAdmin` becomes a hard requirement; that must be treated as an explicit contract decision, not slipped into this bootstrap endpoint.

---

## Risks

- Missing account after successful authentication is unlikely but must not become a 500 if the query handler reloads the account.
- Non-active account behavior is already enforced by the authenticator; duplicating it in the handler must use the same generic failure semantics and avoid leaking lifecycle details.
- Query handler registration may be easy to miss because current use cases are mostly command handlers and services are explicitly tagged for buses.
- OpenAPI documentation should stay in sync with the controller because issue #46 established docs for auth endpoints.
- The frontend may later want admin/bootstrap capability flags, but this endpoint should remain identity-only until a separate permission/admin contract exists.

---

## Next Artifact

Expected next artifact during implementation:

```text
docs/tasks/issues-50/checklist.md
docs/tasks/issues-50/implementation.md
```
