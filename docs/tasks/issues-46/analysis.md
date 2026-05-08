# Issue #46 Analysis: Add OpenAPI Documentation for IdentityAccess Auth API

Branch: `feature/46-add-openapi-documentation-for-identityaccess-auth-api`
Scope: analysis only — no implementation

---

## 1. Issue Summary

Expose a generated OpenAPI contract for the current IdentityAccess auth HTTP endpoints via NelmioApiDocBundle.

Initial scope:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
```

Optional (only if implemented and stable):

```text
GET /api/auth/me
```

Direction: NelmioApiDocBundle with PHP attributes. No API Platform. OpenAPI concerns stay in Interfaces/Http layer only.

---

## 2. MemPalace Result Summary

### Queries run

- `OpenAPI NelmioApiDocBundle auth API documentation`
- `plainPassword refresh token HttpOnly cookie JWT access token auth contract`

### Findings

MemPalace returned highly relevant context:

**ADR-0005 (Use JWT Access Tokens and HttpOnly Refresh Cookies)** — Accepted:

- JWT access token: short-lived (15 min), returned in JSON from login and refresh.
- Refresh token: opaque, stored in HttpOnly Secure cookie, never returned in JSON.
- Cookie name: `refresh_token`, Path: `/api/auth`, HttpOnly, Secure, SameSite=Lax.
- Login response: `{ "accessToken": "jwt", "tokenType": "Bearer", "expiresIn": 900 }`.
- Registration does not issue tokens or cookies.
- Only `active` accounts receive tokens.
- Error codes: `invalid_credentials` (401), `account_not_active` (403), `invalid_refresh_token` (401).
- Refresh/logout require `X-CSRF-Intent: auth-refresh` header.

**Authentication API Strategy** — Accepted:

- Frontend stores access token in memory only.
- Frontend uses `Authorization: Bearer <accessToken>` for protected endpoints.
- `POST /api/auth/refresh` and `POST /api/auth/logout` require `credentials: include` (cookie-based).
- `GET /api/auth/me` is a planned endpoint returning basic identity.

**Issue #36 Analysis** — Previous branch analysis:

- Registration flow confirmed, response confirmed: `{ "accountId": "...", "status": "pending_approval" }`.
- Registration intentionally does not issue access tokens, create refresh tokens, or set cookies.
- MemPalace context is consistent with current code.

### Conflicts with memory

None found. Memory matches repository source exactly.

---

## 3. Files Inspected

| File | Reason |
|---|---|
| `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` | Auth contract rules, token shapes, cookie config |
| `docs/architecture/http-controller-rules.md` | Controller shape, error response format |
| `src/.../Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Register route, DTO, response |
| `src/.../Interfaces/Http/Controller/Auth/LoginController.php` | Login route, DTO, token/cookie response |
| `src/.../Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php` | Refresh route, CSRF header, cookie rotation |
| `src/.../Interfaces/Http/Controller/Auth/LogoutController.php` | Logout route, CSRF header, cookie clear, 204 |
| `src/.../Interfaces/Http/Request/RegisterAccountRequestDto.php` | Confirmed `plainPassword`, not `password` |
| `src/.../Interfaces/Http/Request/LoginRequestDto.php` | Confirmed `plainPassword`, not `password` |
| `src/.../Application/AuthenticateAccount/AuthenticateAccountResult.php` | Login response fields |
| `src/.../Application/AuthenticateAccount/AuthenticatedAccount.php` | Account sub-object shape |
| `src/.../Application/RefreshAuthentication/RefreshAuthenticationResult.php` | Refresh response fields |
| `src/.../Application/RegisterAccount/RegisterAccountResult.php` | Register response fields |
| `src/.../Interfaces/Http/EventSubscriber/IdentityAccessApiExceptionSubscriber.php` | Exception dispatch chain |
| `src/.../Interfaces/Http/Exception/RegistrationExceptionMapper.php` | 409 email_already_registered |
| `src/.../Interfaces/Http/Exception/AuthenticationExceptionMapper.php` | 401 invalid_credentials, 403 account_not_active |
| `src/.../Interfaces/Http/Exception/RefreshTokenExceptionMapper.php` | 401 invalid_refresh_token |
| `src/.../Interfaces/Http/Exception/ValidationExceptionMapper.php` | 422 validation_failed |
| `src/.../Interfaces/Http/Exception/BadRequestExceptionMapper.php` | 400 invalid_request |
| `tests/.../Controller/Auth/RegisterAccountControllerTest.php` | Confirmed response keys, no password in response |
| `tests/.../Controller/Auth/RefreshAuthenticationControllerTest.php` | Confirmed test infrastructure |
| `composer.json` | Checked for NelmioApiDocBundle — not installed |
| `config/packages/` | Checked for existing OpenAPI config — none |
| `src/.../Interfaces/Http/OpenApi/SchemaDescriber.php` | Custom Nelmio describer that scans Schema/ directory and merges standalone schema classes into components.schemas |

Files NOT inspected (out of scope):

- Project, Issue, Board, Workflow bounded contexts
- Admin controllers
- Domain model internals (Account, RefreshSession aggregates)

---

## 4. Current Auth Endpoint Inventory

### 4.1 POST /api/auth/register

- **Route:** `/api/auth/register`
- **Method:** POST
- **Controller:** `App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\RegisterAccountController`
- **Request DTO:** `RegisterAccountRequestDto`
- **Request body fields:**

```json
{
  "email": "user@example.com",
  "plainPassword": "Password123!",
  "name": "User Name"
}
```

- **Constraints:** `email` NotBlank+Email; `plainPassword` NotBlank+Length(min:8,max:4096); `name` NotBlank+Length(max:100)
- **Success status:** 201 Created
- **Success response:**

```json
{
  "accountId": "7f88c85d-4ef6-4f5e-88f9-c8c4605d208c",
  "status": "pending_approval"
}
```

- **Cookie side effects:** None. Registration does not set any cookie.
- **Token side effects:** None. Registration does not issue access tokens or refresh tokens.
- **Error mappings:** 400 `invalid_request`, 422 `validation_failed`, 409 `email_already_registered`

---

### 4.2 POST /api/auth/login

- **Route:** `/api/auth/login`
- **Method:** POST
- **Controller:** `App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\LoginController`
- **Request DTO:** `LoginRequestDto`
- **Request body fields:**

```json
{
  "email": "user@example.com",
  "plainPassword": "Password123!"
}
```

- **Constraints:** `email` NotBlank+Email; `plainPassword` NotBlank
- **Success status:** 200 OK
- **Success response:**

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

- **Cookie side effects:** Sets `refresh_token` HttpOnly Secure SameSite=Lax cookie on Path `/api/auth`. Cookie value is an opaque random token — never returned in JSON.
- **Required headers:** None (standard Content-Type: application/json)
- **Error mappings:** 400 `invalid_request`, 422 `validation_failed`, 401 `invalid_credentials`, 403 `account_not_active`

---

### 4.3 POST /api/auth/refresh

- **Route:** `/api/auth/refresh`
- **Method:** POST
- **Controller:** `App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\RefreshAuthenticationController`
- **Request body:** None
- **Required headers:** `X-CSRF-Intent: auth-refresh` — controller validates this directly; missing/wrong value returns 403 `csrf_intent_required` and clears cookie
- **Cookie input:** `refresh_token` read from cookie; frontend must send with `credentials: include`
- **Success status:** 200 OK
- **Success response:**

```json
{
  "accessToken": "<jwt>",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

- **Cookie side effects:** Rotates `refresh_token` cookie. New opaque token replaces old token. Cookie cleared if CSRF check fails or token is invalid.
- **Error mappings:** 403 `csrf_intent_required` (CSRF header missing or wrong), 401 `invalid_refresh_token` (missing/expired/revoked/reused token)

---

### 4.4 POST /api/auth/logout

- **Route:** `/api/auth/logout`
- **Method:** POST
- **Controller:** `App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth\LogoutController`
- **Request body:** None
- **Required headers:** `X-CSRF-Intent: auth-refresh` — controller validates this directly; missing/wrong value returns 403 `csrf_intent_required` and clears cookie
- **Cookie input:** `refresh_token` cookie used if present; idempotent if absent/invalid
- **Success status:** 204 No Content (empty body)
- **Cookie side effects:** Always clears `refresh_token` cookie regardless of validity
- **Error mappings:** 403 `csrf_intent_required` (CSRF header missing or wrong)

---

### 4.5 GET /api/auth/me

**Not implemented.** No controller exists for this endpoint. Do not include in OpenAPI documentation scope for this issue.

---

## 5. Contract Drift Findings

### plainPassword — No drift found

Both `RegisterAccountRequestDto` and `LoginRequestDto` use `plainPassword` as the public JSON field name. No `password` field exists in either DTO. This is consistent with ADR-0005 and the issue contract rule.

### Refresh token in JSON — No drift found

The `rawRefreshToken` value from `AuthenticateAccountResult` is used only to set the `refresh_token` cookie. It is not present in the JSON response body. Same for `RefreshAuthenticationResult`. Neither result exposes a `refreshToken` or `refresh_token` JSON field.

### Registration response — No drift found

Registration response contains only `accountId` and `status`. Tests explicitly assert that `password`, `plainPassword`, `passwordHash`, `accessToken`, and `refreshToken` are not present. This matches ADR-0005.

### Login account sub-object — Note

Login response includes an `account` sub-object with `{ id, email, name, status }`. ADR-0005 specifies only the token fields in the initial login response shape. The `account` sub-object is a documented extension beyond the ADR's minimal example. It is implemented and tested — document it as-is.

---

## 6. Error Response Shapes

All error responses follow a consistent envelope shape implemented by the exception mappers.

### 6.1 Validation error (422)

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The request payload is invalid.",
    "violations": [
      {
        "field": "email",
        "message": "This value is not a valid email address."
      }
    ]
  }
}
```

Mapper: `ValidationExceptionMapper`. Triggered by `HttpException` wrapping `ValidationFailedException`, or by `InvalidEmail`/`InvalidAccountName` domain exceptions.

### 6.2 Bad request (400)

```json
{
  "error": {
    "code": "invalid_request",
    "message": "Invalid request body."
  }
}
```

Mapper: `BadRequestExceptionMapper`. Triggered by `BadRequestHttpException` (malformed JSON, unreadable body).

### 6.3 Email already registered (409)

```json
{
  "error": {
    "code": "email_already_registered",
    "message": "Email is already registered."
  }
}
```

Mapper: `RegistrationExceptionMapper`.

### 6.4 Invalid credentials (401)

```json
{
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Mapper: `AuthenticationExceptionMapper`.

### 6.5 Account not active (403)

```json
{
  "error": {
    "code": "account_not_active",
    "message": "Account is not active."
  }
}
```

Mapper: `AuthenticationExceptionMapper`.

### 6.6 Invalid refresh token (401)

```json
{
  "error": {
    "code": "invalid_refresh_token",
    "message": "Invalid refresh token."
  }
}
```

Mapper: `RefreshTokenExceptionMapper`. Also clears `refresh_token` cookie.

### 6.7 CSRF intent required (403)

```json
{
  "error": {
    "code": "csrf_intent_required",
    "message": "CSRF intent header is required."
  }
}
```

Implemented inline in `RefreshAuthenticationController` and `LogoutController` (not via exception mapper). Also clears `refresh_token` cookie.

---

## 7. Current OpenAPI / Nelmio State

- **NelmioApiDocBundle installed:** Yes — `nelmio/api-doc-bundle` v5.10.0
- **Nelmio routes config:** `config/routes/dev/nelmio_api_doc.yaml` — dev-only, `/api/doc` and `/api/doc.json`
- **Nelmio packages config:** `config/packages/nelmio_api_doc.yaml` — bearerAuth scheme, path patterns
- **OpenAPI routes exposed:** `/api/doc.json` works; `/api/doc` was broken (swagger_ui controller missing)
- **Root cause:** `symfony/asset` was not installed. NelmioApiDocBundle v5 removes `nelmio_api_doc.controller.swagger_ui` at container compile time if `Symfony\Component\Asset\Packages` does not exist. `TwigBundle` alone is not sufficient — `symfony/asset` must also be present.
- **Fix applied:** `composer require symfony/asset` (v8.0.8). No route or controller changes needed.

### Swagger UI dependency requirements (NelmioApiDocBundle v5)

For `/api/doc` (Swagger UI HTML) to work, both must be installed:

1. `symfony/twig-bundle` — TwigBundle must be registered in `config/bundles.php`
2. `symfony/asset` — `Symfony\Component\Asset\Packages` class must exist

Missing either causes `nelmio_api_doc.controller.swagger_ui` to be removed at compile time.

### 7.1 Nelmio and Standalone Schema Classes

Nelmio does NOT automatically include standalone schema-only classes (classes annotated with `#[OA\Schema]` but not connected to any route) in `components.schemas`. Only classes that are directly referenced from a scanned controller route are picked up by Nelmio's default route-scanning mechanism.

**The fix:** A custom `SchemaDescriber` was created at `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/SchemaDescriber.php`. It scans the `OpenApi/Schema/` directory and merges all schema definitions into the generated OpenAPI `components` output. `SchemaDescriber` is documentation infrastructure only — it carries no business logic and does not change any endpoint behavior.

It is registered in `config/services.yaml` with the `nelmio_api_doc.describer` tag at priority 100:

```yaml
App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\SchemaDescriber:
  tags:
    - { name: nelmio_api_doc.describer, priority: 100 }
```

---

## 8. Security Documentation Needs

### Bearer JWT scheme

Protected endpoints require:

```http
Authorization: Bearer <accessToken>
```

The `BearerTokenAuthenticator` infrastructure class handles verification. The `GET /api/auth/me` endpoint (not yet implemented) is the first example. Future protected endpoints will use this scheme.

For this issue, the Bearer scheme should be defined in the OpenAPI security definitions so it is ready for use.

### Refresh cookie behavior

The `refresh_token` cookie is:

```text
Name:     refresh_token
Path:     /api/auth
HttpOnly: true
Secure:   true
SameSite: Lax
```

OpenAPI behavior for cookie-based auth:

- `POST /api/auth/refresh` and `POST /api/auth/logout` consume the cookie automatically via browser.
- Frontend must use `credentials: include` (or `credentials: "include"` in fetch).
- The cookie is not readable by JavaScript.
- OpenAPI must NOT document `refresh_token` as a JSON request or response field.
- Cookie send behavior on refresh/logout can be documented as a prose note on the endpoint.

### CSRF header

`POST /api/auth/refresh` and `POST /api/auth/logout` both require:

```http
X-CSRF-Intent: auth-refresh
```

This is implemented in both controllers. OpenAPI must document this as a required header parameter on both endpoints.

---

## 9. Risks / Blockers / Open Questions

### Non-blocking notes

1. **Login `account` sub-object not in ADR-0005 minimal example.** The implemented response includes `account: { id, email, name, status }` beyond the ADR's minimal example. This is implemented and tested — document the actual shape.

2. **`csrf_intent_required` is not in exception mapper.** The 403 CSRF failure response is returned inline by the controllers, not via the centralized exception subscriber. OpenAPI must document it as a possible response for `/api/auth/refresh` and `/api/auth/logout`. This does not block OpenAPI documentation.

3. **`GET /api/auth/me` not implemented.** Excluded from this issue scope.

4. **NelmioApiDocBundle version selection.** The latest stable `nelmio/api-doc-bundle` (v5.x for Symfony 7) must be confirmed against the project's Symfony version during implementation. Check `symfony/framework-bundle` version in `composer.json` before installing.

5. **Dev/stage-only exposure policy must be decided.** The `/api/doc` and `/api/doc.json` routes should not be exposed in production by default. NelmioApiDocBundle supports environment-based route restriction. This must be addressed in config.

6. **OpenAPI security scheme placement.** The `BearerAuth` security scheme should be defined globally in Nelmio config, not per-endpoint. Endpoints that require it will reference it by name.

### Stop conditions triggered

None. All four auth endpoints are implemented and stable. Request/response shapes are deterministic from code and tests. Nelmio version and config can be planned safely. No ADRs are missing.

---

## 10. Files Considered for OpenAPI Attribute Placement

OpenAPI attributes will be placed on controllers and on standalone schema classes under `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/`. The rationale:

- All four controllers are simple and stable.
- Attributes on controllers follow the NelmioApiDocBundle standard pattern.
- No business logic lives in controllers per `http-controller-rules.md`.
- Attributes are documentation-only — they carry no behavior.

**Important:** Standalone schema-only classes (classes with `#[OA\Schema]` that are not connected to any route) are NOT automatically included in `components.schemas` by Nelmio. A custom `SchemaDescriber` must be created and registered as a `nelmio_api_doc.describer` service to scan the `Schema/` directory and merge all schema definitions into the generated OpenAPI spec. Without `SchemaDescriber`, schema `$ref` values in controller attributes will not resolve to anything in `components.schemas`.
