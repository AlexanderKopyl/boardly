# Issue #14 Analysis: RegisterAccount HTTP API Endpoint

## 1. Issue Summary

Issue #14 adds the public `POST /api/auth/register` HTTP endpoint that wraps the already-implemented `RegisterAccountHandler` use case. The controller must stay thin: parse the JSON request body, construct `RegisterAccountCommand`, invoke the handler, map the result or exception to an HTTP response. No business logic, no Doctrine, no password hashing, no direct aggregate access belongs in the controller.

---

## 2. Files Inspected and Why

| File | Reason |
|------|--------|
| `docs/design/authentication-api-strategy.md` | Route contract, response shape, error codes, account lifecycle rules |
| `docs/design/account-domain-model.md` | Result objects, exception layer ownership, error → HTTP mapping |
| `docs/tasks/issues-12/analysis.md` | Confirmed issue #12 scope and dependency list |
| `docs/tasks/issues-12/plan.md` | Confirmed implementation plan was followed |
| `docs/adr/0002-use-boardly-context-based-source-structure.md` | Controller placement: `src/Boardly/<Context>/Interfaces/Http/Controller/` |
| `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountCommand.php` | Command shape: `email`, `plainPassword`, `name` |
| `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php` | Handler dependencies and flow |
| `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php` | Result shape: `accountId(): string`, `status(): string` |
| `src/Boardly/IdentityAccess/Application/Exception/EmailAlreadyRegistered.php` | Exception type, construction, message |
| `src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Repository/DoctrineAccountRepository.php` | Confirmed `save()` does NOT flush |
| `tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Repository/DoctrineAccountRepositoryIntegrationTest.php` | Confirmed flush is always called explicitly after save |
| `src/Interfaces/Http/Controller/HealthCheckController.php` | Controller style: `final readonly class`, `__invoke()`, `#[Route]` attribute, `JsonResponse` |
| `tests/Interfaces/Http/Controller/HealthCheckControllerTest.php` | HTTP test style: `WebTestCase`, `createClient()`, `assertResponseStatusCodeSame()` |
| `config/routes.yaml` | Current routing scans `src/Interfaces/Http/Controller/` only |
| `config/services.yaml` | `autowire: true`, `autoconfigure: true`, `App\:` scans all of `src/` |
| `config/packages/security.yaml` | No `access_control` rules; all routes are currently public |
| `tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php` | Domain exception types that propagate from the handler |

---

## 3. Issue #12 Dependency Status

All issue #12 dependencies are present and fully implemented:

| Class/File | Status |
|-----------|--------|
| `RegisterAccountCommand` | ✅ exists |
| `RegisterAccountHandler` | ✅ exists |
| `RegisterAccountResult` | ✅ exists |
| `EmailAlreadyRegistered` | ✅ exists |
| `PasswordHasherInterface` | ✅ exists |
| `ClockInterface` | ✅ exists |
| `IdGeneratorInterface` | ✅ exists |
| `SymfonyPasswordHasher` | ✅ exists |
| `SystemClock` | ✅ exists |
| `UuidGenerator` | ✅ exists |
| Handler unit tests | ✅ exist |

Implementation of issue #14 is unblocked by issue #12.

---

## 4. Existing RegisterAccount Use Case Shape

**`RegisterAccountCommand`**:
```php
new RegisterAccountCommand(
    email: string,
    plainPassword: string,
    name: string,
)
```

**`RegisterAccountHandler::__invoke(RegisterAccountCommand $command): RegisterAccountResult`** flow:
1. `Email::fromString($command->email())` — validates and normalizes (trim + lowercase)
2. `AccountName::fromString($command->name())` — validates and trims
3. `existsByEmail($email)` — pre-check, throws `EmailAlreadyRegistered` if true
4. `$passwordHasher->hash($command->plainPassword())` — hashes via port
5. `AccountId::fromString($idGenerator->generate())` — wraps generated UUID
6. `$clock->now()` — gets current time
7. `Account::register(...)` — creates pending, non-system-admin account
8. `$accounts->save($account)` — persists (no flush)
9. Returns `RegisterAccountResult($account->id()->value(), $account->status()->value())`

**`RegisterAccountResult`** (application-safe, no forbidden accessors):
- `accountId(): string`
- `status(): string` — always `"pending_approval"` for public registration

---

## 5. Existing Result/Exception Behavior

**`RegisterAccountResult`**:
- Exposes only `accountId()` and `status()`
- Verified by reflection test that no `plainPassword`, `passwordHash`, `accessToken`, `refreshToken`, `cookie`, `account`, or `entity` method exists
- Matches the issue #14 success response spec: `{ "accountId": "...", "status": "pending_approval" }`

**`EmailAlreadyRegistered`** (application exception):
- Extends `RuntimeException`
- Created with `::withEmail(Email $email)`
- Message: `"Email is already registered."`
- Maps to → `409 Conflict`

**Domain exceptions that propagate from handler value-object construction**:
- `InvalidEmail` — thrown by `Email::fromString()` for malformed email
- `InvalidAccountName` — thrown by `AccountName::fromString()` for empty/too-long name
- `InvalidPasswordHash` — thrown by `PasswordHash::fromString()` if the hasher returns an unsupported format

All three map to → `400 Bad Request`.

---

## 6. Current Source Placement Conventions

ADR-0002 specifies:

> "Context-specific controllers, CLI commands, webhook adapters, request DTOs, and presenters live under each context's `Interfaces` layer when they belong to that context."

Future routing direction from ADR-0002:
```yaml
controllers:
  resource: '../src/Boardly/*/Interfaces/Http/Controller/'
  type: attribute
```

The `HealthCheckController` lives in `src/Interfaces/Http/Controller/` because it is a generic technical controller, not a product-specific bounded context controller. ADR-0002 explicitly allows this: "Existing generic technical controllers such as health checks may remain outside Boardly contexts if they are not product-specific."

The `RegisterAccountController` is product-specific IdentityAccess code and must follow the context-based convention:
```text
src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php
```

---

## 7. Current Routing/Controller Conventions

**`config/routes.yaml`** (current):
```yaml
controllers:
    resource:
        path: ../src/Interfaces/Http/Controller/
        namespace: App\Interfaces\Http\Controller
    type: attribute
```

This currently does **not** scan `src/Boardly/*/Interfaces/Http/Controller/`. Adding the IdentityAccess controller requires updating `routes.yaml` to also scan the new path.

**Controller style** (from `HealthCheckController`):
- `final readonly class`
- Route registered via `#[Route(..., methods: [...])]` PHP attribute
- Single `__invoke()` method
- Returns `JsonResponse` directly
- No base class, no Symfony response helpers beyond `JsonResponse`

**Route naming convention** (from health check): `health_check` — snake_case. Auth routes should follow `api_auth_register`.

---

## 8. Current Test Conventions

**HTTP tests** (from `HealthCheckControllerTest`):
- Extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase`
- Namespace: `App\Tests\<mirror of controller path>`
- Use `self::createClient()`, `$client->request(METHOD, PATH, [...], [...], ['CONTENT_TYPE' => 'application/json'], $body)`
- Use `self::assertResponseStatusCodeSame(201)`
- Use `self::assertJsonStringEqualsJsonString(...)` or decode and assert array

**Application unit tests** (from `RegisterAccountHandlerTest`):
- Extend `PHPUnit\Framework\TestCase`
- Inline fake classes in the same test file (no separate test double files)
- No kernel, no Doctrine, no HTTP in application unit tests

The HTTP controller test for issue #14 will extend `WebTestCase`. It will boot the Symfony kernel and hit the real route.

---

## 9. Current Persistence/Flush/Transaction Boundary

`DoctrineAccountRepository::save(Account $account): void` calls `$this->entityManager->persist(...)` only. **It does not call `flush()`.**

All integration tests call `$this->entityManager->flush()` explicitly after `$this->repository->save(...)`. This is confirmed in `DoctrineAccountRepositoryIntegrationTest`.

This means: **the HTTP controller or a surrounding mechanism must flush the entity manager after a successful handler call.** The controller must not inject `EntityManagerInterface` directly (violates the "must not use Doctrine directly" constraint).

**Recommended solution**: Add a Symfony event subscriber in `src/Shared/Infrastructure/Symfony/EventSubscriber/DoctrineFlushListener.php` that listens on `kernel.response` (at a negative priority such as `-10`) and calls `$entityManager->flush()`. This is transparent to the controller and keeps the controller Doctrine-free.

This is a small focused file with one responsibility. It does not change repository semantics or require migration changes.

**Risk**: If a future transaction rolls back, the flush listener could flush a partial state. For issue #14, there is only one write (single account persist), so the risk is minimal. A more complete transactional story can be added later.

---

## 10. Risks, Blockers, and Unclear Areas

| Item | Severity | Detail |
|------|----------|--------|
| No HTTP flush boundary | Medium | `save()` does not flush. Requires a flush mechanism that keeps the controller Doctrine-free. The event subscriber approach is recommended. |
| Response shape discrepancy | Low | The design doc specifies `{ "id", "email", "name", "status" }`. The issue spec and `RegisterAccountResult` use `{ "accountId", "status" }`. Issue #14 follows the issue spec. |
| Validation error code discrepancy | Low | The design doc specifies `422 Unprocessable Entity` with `validation_failed` for input errors. The issue spec requires `400 Bad Request`. Issue #14 follows the issue spec. Document the divergence. |
| Request `password` vs `plainPassword` | Low | The design doc uses `"password"` as the JSON key. The issue spec uses `"plainPassword"`. The controller parses the request body, so the JSON key is a controller concern. Issue #14 uses `"plainPassword"` per the task spec. |
| `InvalidPasswordHash` from hasher | Low | If `SymfonyPasswordHasher` produces a format not accepted by `PasswordHash::fromString()`, the domain exception propagates. Should map to 500 Internal Server Error (infrastructure failure), not 400. |
| Routes.yaml must be updated | Low | `config/routes.yaml` must be extended to scan `src/Boardly/*/Interfaces/Http/Controller/`. This affects all future bounded context controllers. |
| Security firewall | None | No `access_control` rules are configured. The endpoint is public with no changes required to `security.yaml`. |
| Composer scripts | None | `composer test` runs `phpunit`; `composer phpstan` runs analysis; `composer qa` runs both. Use these for verification. |

**No blockers**: implementation of issue #14 may proceed after the plan is reviewed.