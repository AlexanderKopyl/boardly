# Issue #14 Plan: RegisterAccount HTTP API Endpoint

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the public `POST /api/auth/register` HTTP endpoint that wraps the existing `RegisterAccountHandler` use case, returns `201 Created` with `{ "accountId", "status": "pending_approval" }`, and maps domain/application exceptions to correct HTTP error responses.

**Architecture:** Thin controller in the IdentityAccess `Interfaces` layer; request JSON parsing is manual (no Symfony Serializer or form); a Symfony event subscriber handles entity-manager flush so the controller stays Doctrine-free; all business rules remain in the existing application/domain layer.

**Tech Stack:** Symfony 7, PHP 8.3, `JsonResponse`, `WebTestCase`, `RegisterAccountHandler` (already wired via autowire).

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Parse JSON, build command, call handler, return 201 / 400 / 409 |
| Modify | `config/routes.yaml` | Add `src/Boardly/*/Interfaces/Http/Controller/` routing resource |
| Create | `src/Shared/Infrastructure/Symfony/EventSubscriber/DoctrineFlushListener.php` | Flush EntityManager on `kernel.response` so controller stays Doctrine-free |
| Create | `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php` | HTTP integration tests for all success and error cases |

No other files need to change.

---

## 1. Controller Class Name and Path

```text
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php
```

Namespace:
```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;
```

Why: ADR-0002 places context-specific controllers under `src/Boardly/<Context>/Interfaces/Http/Controller/`. Auth controllers group under an `Auth/` subdirectory to match future `/api/auth/*` routes.

---

## 2. Route Declaration Style

Use PHP attribute routing, consistent with `HealthCheckController`:

```php
#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
```

Routes.yaml must be updated to scan the new path. The existing entry scans only `src/Interfaces/Http/Controller/`. Add a second entry:

```yaml
# config/routes.yaml
controllers:
    resource:
        path: ../src/Interfaces/Http/Controller/
        namespace: App\Interfaces\Http\Controller
    type: attribute

boardly_controllers:
    resource:
        path: ../src/Boardly/
        namespace: App\Boardly
    type: attribute
    exclude:
        - '../src/Boardly/**/{Domain,Application,Infrastructure}/**'
```

Or, more narrowly scoped (preferred — avoids scanning non-controller directories):

```yaml
boardly_controllers:
    resource:
        path: ../src/Boardly/IdentityAccess/Interfaces/Http/Controller/
        namespace: App\Boardly\IdentityAccess\Interfaces\Http\Controller
    type: attribute
```

**Recommendation**: use the narrowly scoped entry first. Expand to the wildcard glob when a second bounded context adds controllers.

---

## 3. Request JSON Parsing Strategy

Use manual JSON parsing from `$request->getContent()`. No Symfony Serializer, no DTO class, no `#[MapRequestPayload]`.

Reason: the request has three fields (`email`, `plainPassword`, `name`), all required strings. Manual parsing is minimal, explicit, and produces the correct 400 response without extra infrastructure.

Parsing steps:
1. `json_decode($request->getContent(), true)` — returns `null` if invalid JSON
2. If result is not an array → `400 Bad Request`, code `invalid_request`
3. Check that `email`, `plainPassword`, and `name` keys exist and are non-empty strings → `400 Bad Request`, code `validation_failed`
4. Pass the three raw strings to `new RegisterAccountCommand(...)` — domain value objects handle further validation

---

## 4. RegisterAccountCommand Construction

```php
$command = new RegisterAccountCommand(
    email: $data['email'],
    plainPassword: $data['plainPassword'],
    name: $data['name'],
);
```

`RegisterAccountCommand` accepts raw strings. Domain value objects (`Email`, `AccountName`) perform normalization and validation when the handler runs.

---

## 5. RegisterAccountHandler Invocation

The controller declares `RegisterAccountHandler` as a constructor dependency. Symfony autowires it automatically (no manual service config needed — `services.yaml` already scans all of `src/`).

```php
public function __construct(
    private readonly RegisterAccountHandler $handler,
) {}
```

Invocation:

```php
$result = ($this->handler)($command);
```

or equivalently:

```php
$result = $this->handler->__invoke($command);
```

The handler returns `RegisterAccountResult` on success or throws an exception.

---

## 6. Success Response Shape and Status Code

```http
201 Created
Content-Type: application/json

{
    "accountId": "018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d",
    "status": "pending_approval"
}
```

Build from result:

```php
return new JsonResponse(
    [
        'accountId' => $result->accountId(),
        'status' => $result->status(),
    ],
    JsonResponse::HTTP_CREATED,
);
```

**Note on design-doc divergence**: `authentication-api-strategy.md` specifies `{ "id", "email", "name", "status" }`. Issue #14 spec and `RegisterAccountResult` both use `{ "accountId", "status" }`. Issue #14 follows the issue spec. The response shape can be expanded in a later issue if the design doc is revised.

---

## 7. Error Response Shape

All error responses use a consistent envelope:

**400 Bad Request — invalid JSON**:
```json
{
    "error": {
        "code": "invalid_request",
        "message": "Invalid JSON body."
    }
}
```

**400 Bad Request — missing or wrong-type fields, domain validation failure**:
```json
{
    "error": {
        "code": "validation_failed",
        "message": "Validation failed."
    }
}
```

**409 Conflict — duplicate email**:
```json
{
    "error": {
        "code": "email_already_registered",
        "message": "Email is already registered."
    }
}
```

**Note on design-doc divergence**: The design doc specifies `422 Unprocessable Entity` with a field-level errors map for validation failures. Issue #14 spec requires `400 Bad Request`. Issue #14 follows the issue spec. The `422` path and field map can be added in a later issue if the design is revised.

---

## 8. Exception/Error Mapping

| Exception | HTTP Status | Error Code |
|-----------|------------|------------|
| `EmailAlreadyRegistered` (application) | `409 Conflict` | `email_already_registered` |
| `InvalidEmail` (domain) | `400 Bad Request` | `validation_failed` |
| `InvalidAccountName` (domain) | `400 Bad Request` | `validation_failed` |
| `InvalidPasswordHash` (domain, from infra hasher) | `500 Internal Server Error` | Let Symfony handle — this is an infrastructure failure, not user input |
| `json_decode` returns non-array | `400 Bad Request` | `invalid_request` |
| Missing or non-string `email`/`plainPassword`/`name` | `400 Bad Request` | `validation_failed` |

Do not catch `InvalidPasswordHash` and return 400 — it means `SymfonyPasswordHasher` produced a hash that `PasswordHash::fromString()` rejected, which is an infrastructure misconfiguration.

---

## 9. Flush/Transaction Boundary

`DoctrineAccountRepository::save()` calls `persist()` only — no flush. The EntityManager must be flushed after a successful handler call.

To keep the controller Doctrine-free, add:

```text
src/Shared/Infrastructure/Symfony/EventSubscriber/DoctrineFlushListener.php
```

This subscriber listens on `KernelEvents::RESPONSE` at priority `-10` and calls:

```php
$this->entityManager->flush();
```

Symfony autowires `EntityManagerInterface` into the subscriber. The listener runs after the controller builds the response, but before Symfony sends it. The controller has no knowledge of flush.

**Risk**: if a later use case needs explicit rollback on response error, this listener must be made smarter. For issue #14 (single write, no rollback scenario), the simple flush-on-response listener is sufficient.

---

## 10. Test Plan

Test file:
```text
tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php
```

Namespace:
```php
namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;
```

Extends `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`.

These are HTTP integration tests. They boot the Symfony kernel, hit the real route, and persist to the test database (migrations must be applied in the test environment).

### Test cases

**Success path:**

- `testSuccessfulRegistrationReturns201()` — POST with valid `email`, `plainPassword`, `name`; assert `201 Created`
- `testSuccessfulRegistrationReturnsAccountId()` — assert response body contains `accountId` as a non-empty string
- `testSuccessfulRegistrationReturnsPendingApprovalStatus()` — assert `status` equals `"pending_approval"`

**Duplicate email:**

- `testDuplicateEmailReturns409()` — register twice with same email; assert second call returns `409`; assert `error.code` is `"email_already_registered"`

**Invalid input — 400:**

- `testInvalidJsonReturns400()` — send `Content-Type: application/json` with body `not json`; assert `400`
- `testMissingEmailReturns400()` — omit `email` field; assert `400`
- `testMissingPlainPasswordReturns400()` — omit `plainPassword` field; assert `400`
- `testMissingNameReturns400()` — omit `name` field; assert `400`
- `testInvalidEmailFormatReturns400()` — send `"email": "not-an-email"`; assert `400`
- `testNonStringEmailReturns400()` — send `"email": 123` (integer); assert `400`

**Response does not expose sensitive data:**

- `testResponseDoesNotExposePassword()` — on success, assert response body does not contain `plainPassword`, `password`, `passwordHash`
- `testResponseDoesNotExposeTokens()` — on success, assert response body does not contain `accessToken`, `refreshToken`
- `testResponseDoesNotExposeCookies()` — on success, assert no `Set-Cookie` header is present
- `testResponseContainsOnlyExpectedKeys()` — on success, decode response body and assert the only top-level keys are `accountId` and `status` (verifies no Account aggregate, Doctrine entity, or internal state is leaked)

**Test helpers:**

```php
private function registerPayload(
    string $email = 'user@example.com',
    string $plainPassword = 'correct horse battery staple',
    string $name = 'Test User',
): string {
    return json_encode([
        'email' => $email,
        'plainPassword' => $plainPassword,
        'name' => $name,
    ], JSON_THROW_ON_ERROR);
}

private function postRegister(string $body): void
{
    $this->client->request(
        'POST',
        '/api/auth/register',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        $body,
    );
}
```

---

## 11. Files Affected During Implementation

| File | Action | Notes |
|------|--------|-------|
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Create | New file |
| `config/routes.yaml` | Modify | Add IdentityAccess controller routing resource |
| `src/Shared/Infrastructure/Symfony/EventSubscriber/DoctrineFlushListener.php` | Create | Flush EntityManager on `kernel.response` |
| `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php` | Create | HTTP integration tests |

**Must not change:**
- `RegisterAccountCommand`, `RegisterAccountHandler`, `RegisterAccountResult`
- `Account`, `Email`, `AccountName`, `PasswordHash`
- `AccountRepositoryInterface`, `DoctrineAccountRepository`
- `config/packages/security.yaml`
- Any migration
- Any existing test

---

## 12. Verification Commands

Run focused HTTP controller tests:
```bash
vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller
```

Run existing application use case tests (regression check):
```bash
vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/RegisterAccount
```

Run existing domain and infrastructure tests (regression check):
```bash
vendor/bin/phpunit tests/Boardly/IdentityAccess
```

Run full test suite:
```bash
composer test
```

Run static analysis:
```bash
composer phpstan
```

Run both:
```bash
composer qa
```

Verify route is registered:
```bash
php bin/console debug:router | grep '/api/auth/register'
```

Verify handler is wired:
```bash
php bin/console debug:container App\\Boardly\\IdentityAccess\\Application\\RegisterAccount\\RegisterAccountHandler --env=test
```

---

## 13. Stop Conditions Before Coding

Stop and reassess if any of the following are true:

- `composer test` fails on `main` before any issue #14 changes
- `php bin/console debug:router | grep health` does not return `health_check` (routing is broken)
- The test database has not had migrations applied (`doctrine:migrations:migrate --env=test`)
- `php bin/console debug:container RegisterAccountHandler --env=test` reports "service not found"
- The `SymfonyPasswordHasher` is not wired to `PasswordHasherInterface` (check with `debug:container PasswordHasherInterface`)
- Any existing test in `tests/Boardly/IdentityAccess` fails before changes are made

All of these indicate a pre-existing environment issue that must be fixed before adding issue #14 code.
