# Issue #14 Plan: RegisterAccount HTTP API Endpoint

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the public `POST /api/auth/register` HTTP endpoint that wraps the existing `RegisterAccountHandler` use case, returns `201 Created` with `{ "accountId", "status": "pending_approval" }`, and maps domain/application exceptions to correct HTTP error responses.

**Architecture:** Thin controller in the IdentityAccess `Interfaces` layer; request body is mapped via `#[MapRequestPayload]` into `RegisterAccountRequestDto` with Symfony Validator constraints (no manual `json_decode` or field validation in the controller); `IdentityAccessApiExceptionSubscriber` maps application/domain exceptions to standardized JSON error responses centrally; an application-level `TransactionalInterface` port wraps handler mutations so the controller and handler stay Doctrine-free; the `DoctrineTransactional` adapter uses `EntityManagerInterface::wrapInTransaction()` to flush and commit in one atomic boundary; all business rules remain in the existing application/domain layer.

**Tech Stack:** Symfony 7, PHP 8.3, `JsonResponse`, `WebTestCase`, `RegisterAccountHandler` (already wired via autowire).

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `src/Shared/Application/Transaction/TransactionalInterface.php` | Application port: wraps mutation callbacks in an atomic transaction boundary |
| Create | `src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php` | Infrastructure adapter: implements `TransactionalInterface` via `EntityManagerInterface::wrapInTransaction()` |
| Modify | `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php` | Inject `TransactionalInterface`; wrap mutation body in `transactional(...)` |
| Create | `src/Boardly/IdentityAccess/Interfaces/Http/Request/RegisterAccountRequestDto.php` | Transport validation DTO: Symfony Validator constraints for `email`, `plainPassword`, `name` |
| Create | `src/Boardly/IdentityAccess/Interfaces/Http/EventSubscriber/IdentityAccessApiExceptionSubscriber.php` | Context-local exception subscriber: maps application/domain exceptions to standardized API error responses |
| Create | `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Receive `RegisterAccountRequestDto` via `#[MapRequestPayload]`, build command, call handler, return 201 |
| Modify | `config/routes.yaml` | Add `src/Boardly/*/Interfaces/Http/Controller/` routing resource (already planned) |
| Create | `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php` | HTTP integration tests: 400 malformed JSON, 422 validation failures, 409 duplicate email, 201 success |

No other files need to change. `DoctrineFlushListener` must not be created.

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
#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
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

## 3. Request DTO and Payload Mapping Strategy

Use `#[MapRequestPayload]` to bind the JSON request body to `RegisterAccountRequestDto`. No manual `json_decode()`. No manual field presence or type checks in the controller.

Per `docs/architecture/http-controller-rules.md`: controllers must not manually parse JSON with `json_decode()` when Symfony request mapping can be used.

```text
src/Boardly/IdentityAccess/Interfaces/Http/Request/RegisterAccountRequestDto.php
```

`RegisterAccountRequestDto` shape:

```php
final readonly class RegisterAccountRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $plainPassword,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
    ) {
    }
}
```

Symfony handles the two failure paths automatically before the controller body executes:
- Malformed or unreadable JSON body → Symfony throws `BadRequestHttpException` → `IdentityAccessApiExceptionSubscriber` returns `400 invalid_request`
- Structurally valid JSON with constraint violations → Symfony throws `HttpException` wrapping `ValidationFailedException` → subscriber returns `422 validation_failed` with field-level violations

The controller receives `$requestDto` only after both paths pass. No controller-level JSON parsing or field checks are needed.

---

## 4. RegisterAccountCommand Construction

```php
$command = new RegisterAccountCommand(
    email: $requestDto->email,
    plainPassword: $requestDto->plainPassword,
    name: $requestDto->name,
);
```

`RegisterAccountCommand` accepts raw strings. Domain value objects (`Email`, `AccountName`) perform normalization and validation when the handler runs. The request DTO must not be passed directly to the handler — the explicit mapping preserves the separation between the HTTP layer and the application layer.

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

All error responses use a consistent envelope. Mapping is handled by `IdentityAccessApiExceptionSubscriber`, not inside the controller.

**400 Bad Request — malformed or unreadable request body**:
```json
{
    "error": {
        "code": "invalid_request",
        "message": "Invalid request body."
    }
}
```

**422 Unprocessable Entity — DTO validation failure**:
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

**409 Conflict — duplicate email**:
```json
{
    "error": {
        "code": "email_already_registered",
        "message": "Email is already registered."
    }
}
```

Per `docs/architecture/http-controller-rules.md`: malformed JSON → `400`; structurally valid JSON with invalid fields → `422`. The earlier issue spec assumption of `400`-for-all-validation is superseded by the HTTP controller rules.

---

## 8. Exception/Error Mapping

All mappings are handled by `IdentityAccessApiExceptionSubscriber`. Per `docs/architecture/http-controller-rules.md`, controllers must not catch expected exceptions locally.

| Source | HTTP Status | Error Code | Handled by |
|--------|------------|------------|------------|
| Malformed/unreadable request body | `400 Bad Request` | `invalid_request` | `BadRequestHttpException` → subscriber |
| DTO constraint violation (`NotBlank`, `Email`, `Length`) | `422 Unprocessable Entity` | `validation_failed` | `HttpException(ValidationFailedException)` → subscriber |
| `EmailAlreadyRegistered` (application) | `409 Conflict` | `email_already_registered` | subscriber |
| `InvalidEmail` (domain, bypass DTO path) | `422 Unprocessable Entity` | `validation_failed` | subscriber |
| `InvalidAccountName` (domain, bypass DTO path) | `422 Unprocessable Entity` | `validation_failed` | subscriber |
| `InvalidPasswordHash` (domain, from infra hasher) | `500 Internal Server Error` | — | Symfony default error handler |

Do not catch `InvalidPasswordHash` and return a client error — it is an infrastructure misconfiguration, not a user input failure.

`InvalidEmail` and `InvalidAccountName` should normally not reach the subscriber because `RegisterAccountRequestDto` catches equivalent transport-level violations earlier. The subscriber handles them for defense-in-depth.

## 8.1. IdentityAccessApiExceptionSubscriber

```text
src/Boardly/IdentityAccess/Interfaces/Http/EventSubscriber/IdentityAccessApiExceptionSubscriber.php
```

Placement: context-local first (per `http-controller-rules.md` section 11). The subscriber intercepts `kernel.exception` events and acts only on `/api/` routes.

Mapping logic:
- `EmailAlreadyRegistered` → `409` with `email_already_registered`
- `HttpException` wrapping `ValidationFailedException` → `422` with `validation_failed` + `violations[]`
- `BadRequestHttpException` (standalone) → `400` with `invalid_request`
- `InvalidEmail` / `InvalidAccountName` → `422` with `validation_failed`
- All other exceptions → not handled; Symfony default behavior applies

Rules that must remain true:
- Subscriber must not contain business rules
- Subscriber must not call repositories or handlers
- Subscriber must not expose raw exception messages, stack traces, or entity internals

---

## 9. Transaction Boundary

`DoctrineAccountRepository::save()` calls `persist()` only — no flush. The EntityManager must be flushed inside an explicit transaction after all writes for the use case are complete.

**Do not** use a `kernel.response` subscriber — that approach is rejected (see `analysis.md` section 9 for the full rationale).

### Implementation sequence

**Step 1 — Add application port:**

```text
src/Shared/Application/Transaction/TransactionalInterface.php
```

```php
namespace App\Shared\Application\Transaction;

interface TransactionalInterface
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
```

Application layer rules: no Doctrine, no EntityManager, no flush, no commit, no rollback in this interface or in any handler that uses it.

**Step 2 — Add infrastructure adapter:**

```text
src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php
```

Implement using `EntityManagerInterface::wrapInTransaction()`:

```text
DoctrineTransactional::transactional($operation)
  -> $entityManager->wrapInTransaction($operation)
     -> begin transaction
     -> execute $operation callback
     -> flush UnitOfWork
     -> commit
     -> rollback + rethrow on exception
```

Use `EntityManagerInterface::wrapInTransaction()`, not `DBAL\Connection::transactional()`. DBAL does not know about ORM-tracked entities; it would commit the DB transaction without flushing pending Doctrine changes.

**Step 3 — Wire the binding:**

Check whether Symfony auto-wiring resolves `TransactionalInterface` to `DoctrineTransactional` automatically (it will if there is exactly one implementation). If not, add an explicit binding in `config/services.yaml`:

```yaml
App\Shared\Application\Transaction\TransactionalInterface: '@App\Shared\Infrastructure\Doctrine\DoctrineTransactional'
```

**Step 4 — Update `RegisterAccountHandler`:**

Inject `TransactionalInterface` and wrap the mutation body:

```text
public function __invoke(RegisterAccountCommand $command): RegisterAccountResult
{
    return $this->transactional->transactional(function() use ($command): RegisterAccountResult {
        // existsByEmail, hash, Account::register, $accounts->save
        return new RegisterAccountResult(...);
    });
}
```

Handler rules that must remain true after this change:

- Handler does not inject `EntityManagerInterface`
- Handler does not call `flush()`, `commit()`, or `rollback()`
- Handler does not know about Doctrine
- `$accounts->save()` calls `persist()` only — no change
- All existing exception types (`EmailAlreadyRegistered`, `InvalidEmail`, `InvalidAccountName`) continue to propagate through the transaction boundary unchanged

**Step 5 — Add controller (after handler is wired):**

See sections 1–8 of this plan. Controller behavior does not change. Controller must not inject `TransactionalInterface`, `EntityManagerInterface`, or any Doctrine type.

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

**Malformed request — 400:**

- `testMalformedJsonReturns400()` — POST with body `{not valid json}`; assert `400`; assert `error.code` is `"invalid_request"`; assert `error.message` is `"Invalid request body."`

**DTO validation failures — 422:**

- `testMissingEmailReturns422()` — omit `email` field; assert `422`; assert `error.code` is `"validation_failed"`; assert `error.violations` is non-empty
- `testBlankEmailReturns422()` — send `"email": ""`; assert `422`
- `testInvalidEmailReturns422()` — send `"email": "not-an-email"`; assert `422`
- `testMissingPlainPasswordReturns422()` — omit `plainPassword` field; assert `422`
- `testBlankPlainPasswordReturns422()` — send `"plainPassword": ""`; assert `422`
- `testTooShortPlainPasswordReturns422()` — send a 7-character password (below `Length(min:8)`); assert `422`
- `testMissingNameReturns422()` — omit `name` field; assert `422`
- `testBlankNameReturns422()` — send `"name": ""`; assert `422`
- `testTooLongNameReturns422()` — send a 101-character name (above `Length(max:100)`); assert `422`

**Response does not expose sensitive data:**

- `testResponseDoesNotExposePassword()` — on success, assert response body does not contain `plainPassword`, `password`, `passwordHash`
- `testResponseDoesNotExposeTokens()` — on success, assert response body does not contain `accessToken`, `refreshToken`
- `testResponseDoesNotExposeCookies()` — on success, assert no `Set-Cookie` header is present
- `testResponseContainsOnlyExpectedKeys()` — on success, decode response body and assert the only top-level keys are `accountId` and `status` (verifies no Account aggregate, Doctrine entity, or internal state is leaked)

**Test helpers:**

```php
/** @return array<string, mixed> */
private function validPayload(?string $email = null): array
{
    return [
        'email' => $email ?? $this->uniqueEmail(),
        'plainPassword' => 'valid-password-123',
        'name' => 'Test User',
    ];
}

private function uniqueEmail(): string
{
    return sprintf('user+%s@example.com', uniqid('', true));
}

/** @param array<string, mixed>|string $payload */
private function postRegister(array|string $payload): void
{
    $content = \is_array($payload)
        ? json_encode($payload, JSON_THROW_ON_ERROR)
        : $payload;

    $this->client->request(
        'POST',
        '/api/auth/register',
        [],
        [],
        ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        $content,
    );
}
```

---

## 11. Files Affected During Implementation

| File | Action | Notes |
|------|--------|-------|
| `src/Shared/Application/Transaction/TransactionalInterface.php` | Create | Application port — no Doctrine dependencies |
| `src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php` | Create | Infrastructure adapter using `wrapInTransaction()` |
| `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php` | Modify | Inject `TransactionalInterface`, wrap mutation in `transactional(...)` |
| `config/routes.yaml` | Modify | Add IdentityAccess controller routing resource |
| `src/Boardly/IdentityAccess/Interfaces/Http/Request/RegisterAccountRequestDto.php` | Create | Transport validation DTO with Symfony Validator constraints |
| `src/Boardly/IdentityAccess/Interfaces/Http/EventSubscriber/IdentityAccessApiExceptionSubscriber.php` | Create | Context-local exception subscriber for IdentityAccess API routes |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` | Create | Thin controller: `#[MapRequestPayload]`, DTO → command, return 201 |
| `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountControllerTest.php` | Create | HTTP integration tests: 400, 422, 409, 201 |

**Must not change:**
- `RegisterAccountCommand`, `RegisterAccountResult`
- `Account`, `Email`, `AccountName`, `PasswordHash`
- `AccountRepositoryInterface`, `DoctrineAccountRepository`
- `config/packages/security.yaml`
- Any migration

**Must not create:**
- `src/Shared/Infrastructure/Symfony/EventSubscriber/DoctrineFlushListener.php` — this approach is rejected

---

## 12. Verification Commands

Verify `TransactionalInterface` is registered in the container:
```bash
php bin/console debug:container App\\Shared\\Application\\Transaction\\TransactionalInterface --env=test
```

Verify `DoctrineTransactional` is wired as the implementation:
```bash
php bin/console debug:container App\\Shared\\Infrastructure\\Doctrine\\DoctrineTransactional --env=test
```

Run existing `RegisterAccountHandler` unit tests after handler modification (regression check):
```bash
vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/RegisterAccount
```

Run focused HTTP controller tests:
```bash
vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller
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

Stop and reassess at specific implementation steps if:

- `src/Shared/Application/Transaction/` already exists with conflicting abstractions — reconcile before adding `TransactionalInterface`
- `DoctrineTransactional` cannot be autowired to `TransactionalInterface` — add explicit binding in `services.yaml` before proceeding
- `RegisterAccountHandler` unit tests require structural rewrites (beyond adding a `TransactionalInterface` test double that executes the callback) — reassess the handler modification strategy
- Exception types (`EmailAlreadyRegistered`, `InvalidEmail`, `InvalidAccountName`) do not propagate through `wrapInTransaction()` correctly — verify before wiring the controller

All of these indicate a structural conflict that must be resolved before continuing.
