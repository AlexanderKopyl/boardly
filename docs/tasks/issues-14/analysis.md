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

## 9. Transaction Boundary Decision

`DoctrineAccountRepository::save(Account $account): void` calls `$this->entityManager->persist(...)` only. **It does not call `flush()`.**

All integration tests call `$this->entityManager->flush()` explicitly after `$this->repository->save(...)`. This is confirmed in `DoctrineAccountRepositoryIntegrationTest`.

This means: **the HTTP controller or a surrounding mechanism must flush the entity manager after a successful handler call.** The controller must not inject `EntityManagerInterface` directly.

### Rejected Approach: kernel.response Flush Listener

A `DoctrineFlushListener` subscribing to `kernel.response` was initially proposed. **This approach is rejected** for the following reasons:

- **Hidden transaction boundary**: flush timing is implicit and determined by the HTTP lifecycle, not the use case boundary.
- **Wrong granularity**: a single subscriber flushes all pending changes for all use cases, making it impossible to rollback only a specific use case's writes.
- **Incompatible with Transactional Outbox**: the Outbox pattern requires business state + outbox record to be committed in one atomic transaction. A kernel.response listener cannot coordinate this — by the time it fires, there is no transaction open.
- **Rollback semantics break down**: if the handler raises an exception after persist, the subscriber may never fire (exception path), but if it does fire (e.g., on an error response), it would flush partial state.
- **Business write consistency should be use-case scoped**: the transaction boundary must open before the first write and commit after the last write of a single use case. HTTP request lifecycle is not the correct scope.

### Accepted Approach: Application-Level Transaction Port

The correct model is an application-level port that wraps the mutation in an explicit transaction:

**Port (application layer):**
```text
src/Shared/Application/Transaction/TransactionalInterface.php
```

```php
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

The application layer (handlers) depends only on `TransactionalInterface`. It has no knowledge of Doctrine, DBAL, EntityManager, flush, commit, or rollback.

**Adapter (infrastructure layer):**
```text
src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php
```

Implemented using `EntityManagerInterface::wrapInTransaction()`:

```text
DoctrineTransactional::transactional(callable $operation)
  -> EntityManagerInterface::wrapInTransaction($operation)
     -> begin transaction
     -> execute callback (handler mutation body)
     -> flush UnitOfWork
     -> commit
     -> rollback on exception
```

`EntityManagerInterface::wrapInTransaction()` is preferred over `DBAL\Connection::transactional()` because it knows about the Doctrine ORM UnitOfWork and calls `flush()` internally before committing. DBAL does not know about ORM-tracked changes.

**Handler usage:**

`RegisterAccountHandler` is updated to inject `TransactionalInterface` and wrap its mutation body:

```text
RegisterAccountHandler::__invoke(RegisterAccountCommand $command)
  -> TransactionalInterface::transactional(function() {
       // $accounts->existsByEmail(...)
       // $passwordHasher->hash(...)
       // Account::register(...)
       // $accounts->save($account)  <-- persist only, no flush
       // return RegisterAccountResult(...)
     })
```

Repositories continue to persist/remove only and must not flush.

**Future Outbox rule (not in issue #14):**

Business state + Outbox record = one DB transaction. RabbitMQ/Messenger publishing fires after commit from a separate publisher. Do not dispatch Messenger directly inside handlers. Do not store outbox records after commit.

---

## 10. Risks, Blockers, and Unclear Areas

| Item | Severity | Detail |
|------|----------|--------|
| `TransactionalInterface` placement conflict | Medium | If `src/Shared/Application/Transaction/` already contains conflicting abstractions, the new port must be reconciled before adding it. Verify by inspecting the existing `Shared` structure. |
| `DoctrineTransactional` autowiring | Medium | Symfony must bind `TransactionalInterface` to `DoctrineTransactional` in `services.yaml` or via an interface alias, since autowiring by interface requires an explicit binding when there is no unique implementation registered. Check that `services.yaml` auto-binding covers `TransactionalInterface`. |
| `RegisterAccountHandler` test rewrites | Low | Wrapping handler mutation in `transactional(...)` requires injecting `TransactionalInterface` into the handler. Existing unit tests must pass a test double for `TransactionalInterface`. If tests use `InMemoryAccountRepository`, the transaction double can execute the callback directly. No large rewrite expected. |
| Transaction wrapping changes public behavior | Low | If `wrapInTransaction()` changes how exceptions propagate (e.g., wraps them), the existing `EmailAlreadyRegistered`, `InvalidEmail`, `InvalidAccountName` exception types must still surface correctly through the boundary. Verify this during implementation. |
| Response shape discrepancy | Low | The design doc specifies `{ "id", "email", "name", "status" }`. The issue spec and `RegisterAccountResult` use `{ "accountId", "status" }`. Issue #14 follows the issue spec. |
| Validation error code discrepancy | Low | The design doc specifies `422 Unprocessable Entity` with `validation_failed` for input errors. The issue spec requires `400 Bad Request`. Issue #14 follows the issue spec. Document the divergence. |
| Request `password` vs `plainPassword` | Low | The design doc uses `"password"` as the JSON key. The issue spec uses `"plainPassword"`. The controller parses the request body, so the JSON key is a controller concern. Issue #14 uses `"plainPassword"` per the task spec. |
| `InvalidPasswordHash` from hasher | Low | If `SymfonyPasswordHasher` produces a format not accepted by `PasswordHash::fromString()`, the domain exception propagates. Should map to 500 Internal Server Error (infrastructure failure), not 400. |
| Routes.yaml must be updated | Low | `config/routes.yaml` must be extended to scan `src/Boardly/*/Interfaces/Http/Controller/`. This affects all future bounded context controllers. |
| Security firewall | None | No `access_control` rules are configured. The endpoint is public with no changes required to `security.yaml`. |
| Composer scripts | None | `composer test` runs `phpunit`; `composer phpstan` runs analysis; `composer qa` runs both. Use these for verification. |

**No blockers**: implementation of issue #14 may proceed after the plan is reviewed.