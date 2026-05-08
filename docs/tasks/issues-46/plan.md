# Issue #46 Implementation Plan: Add OpenAPI Documentation for IdentityAccess Auth API

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expose a complete, accurate OpenAPI 3.1 contract for the four IdentityAccess auth endpoints via NelmioApiDocBundle, accessible at `/api/doc.json` (JSON) and `/api/doc` (UI).

**Architecture:** NelmioApiDocBundle attributes on existing controllers; shared schema classes in `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/`; global `bearerAuth` security scheme; dev/stage-only route exposure.

**Tech Stack:** PHP 8.2+, Symfony 7, NelmioApiDocBundle 4.x/5.x (confirm against installed Symfony version), OpenAPI 3.1 attributes.

**Contract rules that must never be violated:**

- Register and login request bodies use `plainPassword` — never `password`.
- Refresh token is an opaque HttpOnly cookie — never a JSON field.
- `POST /api/auth/refresh` and `POST /api/auth/logout` require `X-CSRF-Intent: auth-refresh`.
- Registration does not return tokens or cookies.
- `GET /api/auth/me` is NOT implemented — do not document it.

---

## File Map

Files to create:

```text
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/ErrorEnvelope.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/ValidationErrorEnvelope.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/Violation.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/RegisterRequest.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/RegisterResponse.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/LoginRequest.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/LoginResponse.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/LoginAccountData.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/AccessTokenResponse.php
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/SchemaDescriber.php
config/packages/nelmio_api_doc.yaml
config/routes/dev/nelmio_api_doc.yaml
```

Files to modify:

```text
composer.json  (add nelmio/api-doc-bundle — via composer require, not manual edit)
config/services.yaml  (register SchemaDescriber as nelmio_api_doc.describer with priority 100)
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginController.php
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LogoutController.php
```

---

## 1. Package Plan

### Symfony version check

Before running `composer require`, confirm the installed Symfony version:

```bash
composer show symfony/framework-bundle | grep versions
```

- Symfony 7.x → use `nelmio/api-doc-bundle ^4.27` or `^5.0` (confirm latest stable)
- Symfony 6.x → use `nelmio/api-doc-bundle ^4.x`

### Install command (run during implementation, not now)

```bash
composer require nelmio/api-doc-bundle
```

NelmioApiDocBundle requires `twig/twig` for the HTML UI. If Twig is not installed:

```bash
composer require twig/twig symfony/twig-bundle
```

Do not run `composer require` now. This is planning only.

---

## 2. Config Plan

### 2.1 config/packages/nelmio_api_doc.yaml

```yaml
nelmio_api_doc:
  documentation:
    openapi: '3.1.0'
    info:
      title: Boardly API
      description: Boardly project and task management API
      version: 0.1.0
    components:
      securitySchemes:
        bearerAuth:
          type: http
          scheme: bearer
          bearerFormat: JWT
  areas:
    path_patterns:
      - ^/api/(?!doc$)
```

Rules:

- `path_patterns` restricts OpenAPI to `/api/` routes only.
- Excludes `/api/doc` itself from the generated spec to avoid self-referential docs.
- `bearerAuth` is defined globally here. Endpoints reference it by name via attribute.
- Do not add `security:` at the global level — only per-endpoint where required.

### 2.2 config/routes/nelmio_api_doc.yaml

```yaml
app.swagger_ui:
  path: /api/doc
  methods: GET
  defaults:
    _controller: nelmio_api_doc.controller.swagger_ui

app.swagger_json:
  path: /api/doc.json
  methods: GET
  defaults:
    _controller: nelmio_api_doc.controller.swagger

```

### 2.3 Dev/stage-only exposure policy

Restrict `/api/doc` and `/api/doc.json` to `dev` and `staging` environments only.

Option A (preferred): Move route file to `config/routes/dev/nelmio_api_doc.yaml` — Symfony loads this only in `dev`.

Option B: Use `when@dev:` or `when@test:` YAML blocks — supported in Symfony 6.3+.

Option C: Firewall rule in `security.yaml` — rejects requests in prod.

**Recommendation: Option A.** Rename file to `config/routes/dev/nelmio_api_doc.yaml`. For staging, add a separate config with appropriate env detection.

Production must not expose `/api/doc` or `/api/doc.json`. This is a security requirement.

---

## 3. Schema Classes Plan

All schema classes live in:

```text
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/
```

These classes exist only as OpenAPI schema references. They contain no business logic.

### 3.1 ErrorEnvelope

Represents non-validation error responses.

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorEnvelope',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'email_already_registered'),
                new OA\Property(property: 'message', type: 'string', example: 'Email is already registered.'),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
final class ErrorEnvelope {}
```

### 3.2 Violation

Represents a single field-level validation violation.

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Violation',
    required: ['field', 'message'],
    properties: [
        new OA\Property(property: 'field', type: 'string', example: 'email'),
        new OA\Property(property: 'message', type: 'string', example: 'This value is not a valid email address.'),
    ],
    type: 'object',
)]
final class Violation {}
```

### 3.3 ValidationErrorEnvelope

Represents the 422 validation_failed response with violations array.

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationErrorEnvelope',
    required: ['error'],
    properties: [
        new OA\Property(
            property: 'error',
            required: ['code', 'message', 'violations'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'validation_failed'),
                new OA\Property(property: 'message', type: 'string', example: 'The request payload is invalid.'),
                new OA\Property(
                    property: 'violations',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Violation'),
                ),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
final class ValidationErrorEnvelope {}
```

### 3.4 RegisterRequest

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['email', 'plainPassword', 'name'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'plainPassword', type: 'string', format: 'password', minLength: 8, maxLength: 4096, example: 'Password123!'),
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'User Name'),
    ],
    type: 'object',
)]
final class RegisterRequest {}
```

**Critical:** Field name is `plainPassword`. The field `password` must not appear.

### 3.5 RegisterResponse

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterResponse',
    required: ['accountId', 'status'],
    properties: [
        new OA\Property(property: 'accountId', type: 'string', format: 'uuid', example: '7f88c85d-4ef6-4f5e-88f9-c8c4605d208c'),
        new OA\Property(property: 'status', type: 'string', example: 'pending_approval'),
    ],
    type: 'object',
)]
final class RegisterResponse {}
```

### 3.6 LoginRequest

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'plainPassword'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'plainPassword', type: 'string', format: 'password', example: 'Password123!'),
    ],
    type: 'object',
)]
final class LoginRequest {}
```

**Critical:** Field name is `plainPassword`. The field `password` must not appear.

### 3.7 LoginAccountData

Sub-object returned inside the login JSON response.

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginAccountData',
    required: ['id', 'email', 'name', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7f88c85d-4ef6-4f5e-88f9-c8c4605d208c'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'name', type: 'string', example: 'User Name'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
    ],
    type: 'object',
)]
final class LoginAccountData {}
```

### 3.8 LoginResponse

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    required: ['accessToken', 'tokenType', 'expiresIn', 'account'],
    properties: [
        new OA\Property(property: 'accessToken', type: 'string', example: 'eyJhbGciOiJSUzI1NiJ9...'),
        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expiresIn', type: 'integer', example: 900),
        new OA\Property(property: 'account', ref: '#/components/schemas/LoginAccountData'),
    ],
    type: 'object',
)]
final class LoginResponse {}
```

**Critical:** `refresh_token` and `refreshToken` must NOT appear as JSON properties here. The refresh token is set as an HttpOnly cookie only.

### 3.9 AccessTokenResponse

Used by `POST /api/auth/refresh`. Simpler than login — no account sub-object.

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AccessTokenResponse',
    required: ['accessToken', 'tokenType', 'expiresIn'],
    properties: [
        new OA\Property(property: 'accessToken', type: 'string', example: 'eyJhbGciOiJSUzI1NiJ9...'),
        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expiresIn', type: 'integer', example: 900),
    ],
    type: 'object',
)]
final class AccessTokenResponse {}
```

**Critical:** `refresh_token` and `refreshToken` must NOT appear here.

---

## 4. Endpoint Documentation Plan

### 4.1 POST /api/auth/register — RegisterAccountController

Add to `RegisterAccountController::__invoke()`:

```php
#[OA\Post(
    path: '/api/auth/register',
    operationId: 'registerAccount',
    summary: 'Register a new account',
    description: 'Creates a new account in pending_approval status. Does not issue access tokens or set cookies.',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest'),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Account registered successfully.',
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterResponse'),
        ),
        new OA\Response(
            response: 400,
            description: 'Malformed or unreadable request body.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: 409,
            description: 'Email is already registered.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: 422,
            description: 'Request payload is invalid (validation failed).',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
        ),
    ],
)]
```

Rules:

- `plainPassword` is the field name in `RegisterRequest`. No `password` field.
- 201 response shape: `{ accountId, status }` only.
- No accessToken, refreshToken, or cookie documented.

---

### 4.2 POST /api/auth/login — LoginController

Add to `LoginController::__invoke()`:

```php
#[OA\Post(
    path: '/api/auth/login',
    operationId: 'loginAccount',
    summary: 'Authenticate and receive an access token',
    description: 'Validates credentials and issues a JWT access token. Sets an HttpOnly refresh_token cookie that is managed by the browser automatically. The refresh token is never returned in the JSON response.',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Login successful. Sets refresh_token cookie (HttpOnly, Secure, SameSite=Lax).',
            content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse'),
        ),
        new OA\Response(
            response: 400,
            description: 'Malformed or unreadable request body.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: 401,
            description: 'Invalid credentials.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: 403,
            description: 'Account exists but is not active (pending_approval, rejected, or disabled).',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
        new OA\Response(
            response: 422,
            description: 'Request payload is invalid (validation failed).',
            content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
        ),
    ],
)]
```

Rules:

- `plainPassword` is the field name in `LoginRequest`. No `password` field.
- `refresh_token` is not a JSON field — it is set as a cookie. Document in response description only.
- `account` sub-object is documented as part of `LoginResponse`.

---

### 4.3 POST /api/auth/refresh — RefreshAuthenticationController

Add to `RefreshAuthenticationController::__invoke()`:

```php
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
            description: 'Token refreshed. Rotates the refresh_token cookie (HttpOnly, Secure, SameSite=Lax).',
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
```

Rules:

- No request body.
- Refresh token comes from HttpOnly cookie — not documented as a JSON input field.
- `X-CSRF-Intent: auth-refresh` is a required header parameter.
- `AccessTokenResponse` does not include `refresh_token` as a JSON field.
- Cookie rotation side effect is noted in description only.

---

### 4.4 POST /api/auth/logout — LogoutController

Add to `LogoutController::__invoke()`:

```php
#[OA\Post(
    path: '/api/auth/logout',
    operationId: 'logout',
    summary: 'Logout and clear the refresh cookie',
    description: 'Revokes the current refresh session (if valid) and clears the refresh_token cookie. Idempotent — returns 204 regardless of whether a valid refresh token was present. Requires the X-CSRF-Intent header.',
    tags: ['Auth'],
    parameters: [
        new OA\Parameter(
            name: 'X-CSRF-Intent',
            in: 'header',
            required: true,
            description: 'Must be exactly "auth-refresh". Prevents CSRF attacks on the cookie-based logout flow.',
            schema: new OA\Schema(type: 'string', enum: ['auth-refresh']),
        ),
    ],
    responses: [
        new OA\Response(
            response: 204,
            description: 'Logged out. refresh_token cookie cleared.',
        ),
        new OA\Response(
            response: 403,
            description: 'X-CSRF-Intent header is missing or incorrect. refresh_token cookie is cleared.',
            content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
        ),
    ],
)]
```

Rules:

- No request body.
- Idempotent — 204 always when CSRF passes, regardless of cookie presence.
- `X-CSRF-Intent: auth-refresh` is a required header parameter.
- No `invalid_refresh_token` error — logout swallows missing/invalid/revoked token silently.

---

## 5. Common Schema Location

All schema PHP classes live in:

```text
src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/
```

These classes are referenced via `ref: '#/components/schemas/<SchemaName>'`.

Ensure `config/packages/nelmio_api_doc.yaml` includes the correct scan path:

```yaml
nelmio_api_doc:
  areas:
    path_patterns:
      - ^/api/(?!doc$)
```

**Important:** Standalone schema-only classes (not connected to any route) are NOT automatically included in `components.schemas` by Nelmio. A custom `SchemaDescriber` must be created and registered as a `nelmio_api_doc.describer` service to scan the `Schema/` directory and merge schemas into the generated OpenAPI spec. See Step 4.1 below.

---

## 6. Implementation Sequence

### Step 1: Install NelmioApiDocBundle

```bash
composer show symfony/framework-bundle | grep versions
composer require nelmio/api-doc-bundle
```

Verify installation succeeded:

```bash
php bin/console debug:container | grep nelmio
```

Expected: several `nelmio_api_doc.*` services listed.

---

### Step 2: Create config/routes/dev/nelmio_api_doc.yaml

File: `config/routes/dev/nelmio_api_doc.yaml`

```yaml
app.swagger_ui:
  path: /api/doc
  methods: GET
  defaults:
    _controller: nelmio_api_doc.controller.swagger_ui

app.swagger_json:
  path: /api/doc.json
  methods: GET
  defaults:
    _controller: nelmio_api_doc.controller.swagger
```

Verify route exists:

```bash
php bin/console debug:router | grep api/doc
```

Expected output:

```text
app.swagger_ui   GET   /api/doc
app.swagger_json GET   /api/doc.json
```

---

### Step 3: Create config/packages/nelmio_api_doc.yaml

File: `config/packages/nelmio_api_doc.yaml`

```yaml
nelmio_api_doc:
  documentation:
    openapi: '3.1.0'
    info:
      title: Boardly API
      description: Boardly project and task management API
      version: 0.1.0
    components:
      securitySchemes:
        bearerAuth:
          type: http
          scheme: bearer
          bearerFormat: JWT
  areas:
    path_patterns:
      - ^/api/(?!doc$)
```

Verify config is loaded:

```bash
php bin/console config:dump nelmio_api_doc
```

---

### Step 4: Create schema classes

Create all nine schema classes under `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/`:

- `ErrorEnvelope.php`
- `Violation.php`
- `ValidationErrorEnvelope.php`
- `RegisterRequest.php`
- `RegisterResponse.php`
- `LoginRequest.php`
- `LoginAccountData.php`
- `LoginResponse.php`
- `AccessTokenResponse.php`

Use the exact class bodies specified in Section 3 of this plan.

---

### Step 4.1: Create SchemaDescriber and register it

Standalone schema classes will NOT appear in `/api/doc.json` `components.schemas` automatically — Nelmio only picks up classes referenced directly from scanned routes. To include all schema classes:

1. Create `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/SchemaDescriber.php`
   - It must implement the `DescriberInterface` (or `DescriptionFillerInterface`) pattern used by NelmioApiDocBundle to scan the `Schema/` directory and merge all `#[OA\Schema]`-annotated classes into the generated OpenAPI `components`.

2. Register in `config/services.yaml`:

   ```yaml
   App\Boardly\IdentityAccess\Interfaces\Http\OpenApi\SchemaDescriber:
     tags:
       - { name: nelmio_api_doc.describer, priority: 100 }
   ```

3. Verify all nine schema classes appear:

   ```bash
   curl -s http://localhost:8000/api/doc.json | jq '.components.schemas | keys'
   ```

   Expected output (order may vary):

   ```json
   [
     "AccessTokenResponse",
     "ErrorEnvelope",
     "LoginAccountData",
     "LoginRequest",
     "LoginResponse",
     "RegisterRequest",
     "RegisterResponse",
     "ValidationErrorEnvelope",
     "Violation"
   ]
   ```

---

### Step 5: Add OpenAPI attributes to controllers

For each controller, add the `#[OA\Post(...)]` attribute as specified in Section 4 of this plan.

Also add the necessary `use OpenApi\Attributes as OA;` import to each controller.

Do not change any controller logic, route definition, or response structure.

Order:

1. `RegisterAccountController`
2. `LoginController`
3. `RefreshAuthenticationController`
4. `LogoutController`

After each controller, verify the endpoint appears in the spec:

```bash
curl -s http://localhost:8000/api/doc.json | jq '.paths | keys'
```

Expected after all four:

```json
[
  "/api/auth/login",
  "/api/auth/logout",
  "/api/auth/refresh",
  "/api/auth/register"
]
```

---

## 7. Verification Plan

### 7.0 Verify components.schemas keys

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas | keys'
```

Expected: all nine schema classes appear as keys:

```json
[
  "AccessTokenResponse",
  "ErrorEnvelope",
  "LoginAccountData",
  "LoginRequest",
  "LoginResponse",
  "RegisterRequest",
  "RegisterResponse",
  "ValidationErrorEnvelope",
  "Violation"
]
```

Verify that key `$ref` values resolve — RegisterRequest, LoginRequest, and AccessTokenResponse must be present:

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas | has("RegisterRequest"), has("LoginRequest"), has("AccessTokenResponse")'
```

Expected: three lines of `true`.

---

### 7.1 Route check

```bash
php bin/console debug:router | grep api/doc
```

Expected:

```text
app.swagger_json   GET   /api/doc.json
app.swagger_ui     GET   /api/doc
```

### 7.2 Fetch spec

```bash
curl -s http://localhost:8000/api/doc.json | jq .
```

Expected: valid JSON with `openapi`, `info`, `paths`, `components` keys.

### 7.3 Verify paths

```bash
curl -s http://localhost:8000/api/doc.json | jq '.paths | keys'
```

Expected:

```json
["/api/auth/login", "/api/auth/logout", "/api/auth/refresh", "/api/auth/register"]
```

### 7.4 Verify plainPassword in register

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas.RegisterRequest.properties | keys'
```

Expected:

```json
["email", "name", "plainPassword"]
```

Verify `password` is NOT present:

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas.RegisterRequest.properties | has("password")'
```

Expected: `false`

### 7.5 Verify plainPassword in login

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas.LoginRequest.properties | keys'
```

Expected:

```json
["email", "plainPassword"]
```

Verify `password` is NOT present:

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.schemas.LoginRequest.properties | has("password")'
```

Expected: `false`

### 7.6 Verify refreshToken is NOT a JSON field

```bash
curl -s http://localhost:8000/api/doc.json | jq '[.components.schemas | to_entries[] | select(.value.properties.refreshToken or .value.properties.refresh_token) | .key]'
```

Expected: `[]` (empty array — no schema has refreshToken or refresh_token as a JSON property)

### 7.7 Verify Bearer security scheme

```bash
curl -s http://localhost:8000/api/doc.json | jq '.components.securitySchemes.bearerAuth'
```

Expected:

```json
{
  "type": "http",
  "scheme": "bearer",
  "bearerFormat": "JWT"
}
```

### 7.8 Verify X-CSRF-Intent on refresh and logout

```bash
curl -s http://localhost:8000/api/doc.json | jq '.paths."/api/auth/refresh".post.parameters'
curl -s http://localhost:8000/api/doc.json | jq '.paths."/api/auth/logout".post.parameters'
```

Expected for both: array containing a parameter with `name: "X-CSRF-Intent"`, `in: "header"`, `required: true`.

### 7.9 Verify register has no cookie/token in 201 response

```bash
curl -s http://localhost:8000/api/doc.json | jq '.paths."/api/auth/register".post.responses."201"'
```

Expected: response schema references `RegisterResponse` which has only `accountId` and `status`.

### 7.10 Static analysis

```bash
php bin/console cache:clear
vendor/bin/phpstan analyse src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/ --level=8
```

Expected: no errors.

### 7.11 Existing tests must still pass

```bash
php bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/
```

Expected: all tests pass. OpenAPI attributes are documentation-only and must not change controller behavior.

### 7.12 Swagger UI check

Open `http://localhost:8000/api/doc` in a browser.

Verify:

- Four auth endpoints visible under `Auth` tag.
- Request body schemas correct for register and login.
- No `password` field in register or login schemas.
- Cookie behavior described in endpoint descriptions.
- `X-CSRF-Intent` header visible for refresh and logout.
- `bearerAuth` scheme visible in Authorize section.

---

## 8. Acceptance Criteria Checklist

- [ ] NelmioApiDocBundle is installed and registered
- [ ] `/api/doc.json` returns valid OpenAPI 3.1 JSON in dev environment
- [ ] `/api/doc` renders Swagger UI in dev environment
- [ ] `POST /api/auth/register` is documented with `plainPassword` field (not `password`)
- [ ] `POST /api/auth/register` 201 response shows `accountId` and `status` only
- [ ] `POST /api/auth/register` 201 response does not include token, cookie, or password fields
- [ ] `POST /api/auth/login` is documented with `plainPassword` field (not `password`)
- [ ] `POST /api/auth/login` 200 response shows `accessToken`, `tokenType`, `expiresIn`, `account` sub-object
- [ ] `POST /api/auth/login` 200 response does not include `refreshToken` or `refresh_token` JSON field
- [ ] `POST /api/auth/refresh` is documented with no request body
- [ ] `POST /api/auth/refresh` documents `X-CSRF-Intent: auth-refresh` as required header
- [ ] `POST /api/auth/refresh` 200 response shows `accessToken`, `tokenType`, `expiresIn` only
- [ ] `POST /api/auth/refresh` 200 response does not include `refreshToken` or `refresh_token` JSON field
- [ ] `POST /api/auth/logout` is documented with no request body
- [ ] `POST /api/auth/logout` documents `X-CSRF-Intent: auth-refresh` as required header
- [ ] `POST /api/auth/logout` 204 response is documented
- [ ] Error envelopes are consistent across all endpoints
- [ ] `bearerAuth` security scheme defined in `components.securitySchemes`
- [ ] `/api/doc` and `/api/doc.json` routes are NOT exposed in production (`config/routes/dev/`)
- [ ] All existing controller tests pass unchanged
- [ ] PHPStan at level 8 passes on new schema classes
- [ ] `GET /api/auth/me` is NOT documented (not yet implemented)

---

## 9. Explicit Non-Goals

- No API Platform — do not introduce it
- No endpoint behavior change — controllers must remain functionally identical
- No auth redesign — do not change token strategy, cookie config, or CSRF mechanism
- No frontend changes — OpenAPI is backend documentation only
- No business logic in OpenAPI attributes — attributes are documentation only
- No `/api/auth/me` documentation — endpoint not implemented
- No production exposure of `/api/doc` or `/api/doc.json` without explicit decision

---

## 10. Stop Conditions Before Implementation

Stop and report instead of guessing if:

- `composer require nelmio/api-doc-bundle` fails due to Symfony version conflict
- NelmioApiDocBundle attribute namespace differs from `OpenApi\Attributes` in installed version
- Dev route restriction approach conflicts with existing Symfony routing config
- Twig is not installed and UI bundle install fails
- PHPStan reports errors in new schema classes that cannot be fixed without modifying controllers
- Any verification check shows `password`, `refreshToken`, or `refresh_token` appearing as JSON schema properties
