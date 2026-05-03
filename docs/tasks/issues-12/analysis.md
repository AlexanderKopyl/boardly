# Issue #12 Analysis: RegisterAccount Use Case

## 1. Issue summary

Issue #12 is the planning step for the `RegisterAccount` application use case in the `IdentityAccess` bounded context.

The planned use case accepts `RegisterAccountCommand(email, plainPassword, name)`, validates and normalizes account input through existing domain value objects, checks whether the normalized email already exists, hashes the plain password through an application port, creates a pending account through `Account::register(...)`, saves it through `AccountRepositoryInterface`, and returns an application-facing `RegisterAccountResult`.

This task is analysis and planning only. No production code, tests, migrations, configuration, controllers, API DTOs, authentication wiring, or frontend code should be changed as part of this task.

## 2. Confirmed dependencies and whether they exist

Existing dependencies:

- `src/Boardly/IdentityAccess/Domain/Model/Account.php` exists.
- `Account::register(...)` exists and creates a pending, non-system-admin account.
- `src/Boardly/IdentityAccess/Domain/Result/AccountRegistrationResult.php` exists.
- `Account::register(...)` returns the existing domain `AccountRegistrationResult`.
- `src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php` exists.
- `Email`, `AccountName`, `PasswordHash`, and `AccountStatus` value objects exist.
- `src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php` exists.
- `src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php` exists.
- `AccountRepositoryInterface::save(Account $account): void` exists.
- `AccountRepositoryInterface::existsByEmail(Email $email): bool` exists.
- `DoctrineAccountRepository` exists and implements `existsByEmail(...)`.
- `AccountEntity` has a database unique constraint on `email`.
- Existing persistence tests cover duplicate normalized email failure at database flush time.

Missing dependencies for issue #12 implementation:

- `RegisterAccountCommand`
- `RegisterAccountHandler`
- application-facing `RegisterAccountResult`
- `EmailAlreadyRegistered` application exception
- `PasswordHasherInterface` application port
- `ClockInterface` technical port, unless added before issue #12
- `IdGeneratorInterface` technical port, unless added before issue #12
- infrastructure adapter for Symfony password hashing
- infrastructure adapter for current time, unless provided elsewhere before implementation
- infrastructure adapter for UUID/id generation, unless provided elsewhere before implementation
- application unit tests for `RegisterAccountHandler`

## 3. Current relevant source structure

Relevant current source:

```text
src/Boardly/IdentityAccess/
+-- Application/
|   +-- Exception/
|   |   +-- AccountNotFound.php
|   +-- Port/
|       +-- AccountRepositoryInterface.php
+-- Domain/
|   +-- Event/
|   |   +-- AccountApproved.php
|   |   +-- AccountDisabled.php
|   |   +-- AccountRegistered.php
|   |   +-- AccountRejected.php
|   +-- Exception/
|   +-- Model/
|   |   +-- Account.php
|   +-- Result/
|   |   +-- AccountApprovalResult.php
|   |   +-- AccountDisableResult.php
|   |   +-- AccountRegistrationResult.php
|   |   +-- AccountRejectionResult.php
|   +-- ValueObject/
|       +-- AccountName.php
|       +-- AccountStatus.php
|       +-- Email.php
|       +-- PasswordHash.php
+-- Infrastructure/
    +-- Persistence/
        +-- Doctrine/
            +-- Entity/AccountEntity.php
            +-- Mapper/AccountMapper.php
            +-- Repository/DoctrineAccountRepository.php

src/Boardly/SharedKernel/
+-- Domain/
    +-- ValueObject/
        +-- AccountId.php

src/Shared/
+-- Application/.gitkeep
+-- Domain/.gitkeep
+-- Infrastructure/.gitkeep
```

Relevant current tests:

```text
tests/Boardly/IdentityAccess/Domain/
tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/
tests/Boardly/SharedKernel/Domain/ValueObject/AccountIdTest.php
```

No `IdentityAccess/Application/RegisterAccount` source or tests exist yet.

## 4. Existing Account domain/result/event behavior

`Account::register(...)` already accepts:

```text
AccountId
Email
PasswordHash
AccountName
DateTimeImmutable createdAt
```

It creates an `Account` with:

```text
status: pending_approval
isSystemAdmin: false
createdAt: createdAt
updatedAt: createdAt
approvedAt: null
rejectedAt: null
disabledAt: null
```

It returns the existing domain `AccountRegistrationResult`, which contains:

- the created `Account`;
- the `AccountRegistered` domain event.

`AccountRegistered` contains:

- `AccountId`
- `Email`
- `isSystemAdmin`
- `registeredAt`

Important existing behavior for issue #12:

- `Account` never receives a plain password.
- `Account` receives only `PasswordHash`.
- public registration path creates `isSystemAdmin = false`.
- public registration path creates `pending_approval`, not `active`.
- domain result/event classes do not depend on Symfony, Doctrine, HTTP, JWT, Redis, OpenSearch, or Messenger.

The implementation must use this existing domain `AccountRegistrationResult`. It must not create another `AccountRegistrationResult` in the application layer.

## 5. Existing repository persistence behavior relevant to registration

`AccountRepositoryInterface` already exposes the needed registration methods:

```php
public function save(Account $account): void;
public function existsByEmail(Email $email): bool;
```

`DoctrineAccountRepository::existsByEmail(...)` queries by the normalized `Email::value()`.

`DoctrineAccountRepository::save(...)` persists new `AccountEntity` rows or updates an existing row by account id. It does not flush directly.

`AccountEntity` declares:

```text
accounts.email unique constraint: uniq_accounts_email
```

Existing integration tests confirm that a duplicate normalized email fails at database level with Doctrine DBAL `UniqueConstraintViolationException` when the entity manager flushes.

Registration implementation should therefore:

- pre-check duplicates with `AccountRepositoryInterface::existsByEmail(...)`;
- throw an explicit application exception when the pre-check finds an existing email;
- still rely on the database unique constraint as the final consistency guarantee.

Mapping a database unique-constraint race to `EmailAlreadyRegistered` is not currently implemented. Because the repository does not flush, that mapping likely belongs with a later transaction/command execution boundary unless issue #12 explicitly introduces a flush/transaction wrapper. Issue #12 should document the race and avoid adding broad Doctrine exception handling in the handler unless the implementation also defines where flush occurs.

## 6. Missing ports/classes/exceptions/adapters/tests

Application use-case classes to add:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountCommand.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php
```

Application exception to add:

```text
src/Boardly/IdentityAccess/Application/Exception/EmailAlreadyRegistered.php
```

IdentityAccess application port to add:

```text
src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
```

Recommended shape:

```php
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
```

Technical shared ports to add if still missing:

```text
src/Shared/Application/Port/ClockInterface.php
src/Shared/Application/Port/IdGeneratorInterface.php
```

Recommended shapes:

```php
interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}

interface IdGeneratorInterface
{
    public function generate(): string;
}
```

The handler should wrap the generated string with `AccountId::fromString(...)`.

Infrastructure adapters to add if still missing:

```text
src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php
src/Shared/Infrastructure/Time/SystemClock.php
src/Shared/Infrastructure/Identity/UuidGenerator.php
```

Configuration may be needed for the Symfony password hasher adapter if autowiring cannot infer the desired hasher. The adapter should use Symfony password-hashing services behind the `PasswordHasherInterface` port. The domain and application handler must not depend on Symfony password-hashing classes directly.

Application tests to add during implementation:

```text
tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php
```

Use fake repository/hasher/clock/id-generator implementations in the test file or test namespace. Do not use Doctrine, Symfony container, real password hashing cost, HTTP, JWT, refresh cookies, Redis, OpenSearch, or Messenger in these application unit tests.

## 7. Boundary decisions

### where command/handler/result go

Place the use-case classes under:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/
```

This matches the existing design document direction and keeps registration as an IdentityAccess application use case.

### where ports go

Place account-password hashing behind:

```text
src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
```

Password hashing is an IdentityAccess application dependency because it is tied to account credentials and should remain hidden from the domain model.

Place generic technical time/id ports under:

```text
src/Shared/Application/Port/
```

`ClockInterface` and `IdGeneratorInterface` are technical abstractions and should not speak Boardly domain language. The handler converts generated ids to `AccountId`.

### where infrastructure password hasher adapter goes

Place the Symfony adapter under:

```text
src/Boardly/IdentityAccess/Infrastructure/Security/
```

The adapter implements the IdentityAccess application port and may depend on Symfony password-hashing services. Symfony classes must not leak into `Domain` or `Application`.

### how duplicate email is handled

The handler should:

1. create `Email` from command input, allowing domain validation/normalization;
2. call `AccountRepositoryInterface::existsByEmail($email)`;
3. throw `EmailAlreadyRegistered` if true;
4. continue registration only when false.

The database unique constraint remains the final guarantee for concurrent duplicate registrations. Mapping a flush-time unique violation to the same application exception should be planned separately unless issue #12 implementation introduces an explicit transaction/flush boundary.

### how domain event/result is handled

The handler should call:

```php
$domainResult = Account::register(...);
$account = $domainResult->account();
```

Then it should save the account through the repository.

Issue #12 should not dispatch Messenger/RabbitMQ messages directly and should not add Outbox behavior unless an Outbox port already exists and the issue is explicitly expanded. The existing domain `AccountRegistrationResult` and its event should be preserved for future event persistence/delivery. The application-facing `RegisterAccountResult` should be mapped from the saved account or domain result and must not expose:

- `Account`
- Doctrine entity
- plain password
- password hash
- access token
- refresh token/cookie

## 8. Risks and trade-offs

- Duplicate-email race: the pre-check is useful for a clear application error, but two concurrent requests can still pass the pre-check. The database unique constraint remains mandatory. Flush-time exception mapping is a separate transaction-boundary concern unless issue #12 defines it explicitly.
- Password hasher adapter: Symfony's password hasher needs a concrete configuration choice. Keep this behind the application port so implementation details can change without changing the handler/domain.
- Generic id generation: a shared `IdGeneratorInterface` returning a string keeps `src/Shared` free of Boardly types, but the handler must validate/wrap the generated value with `AccountId::fromString(...)`.
- Application result duplication: `RegisterAccountResult` is justified as an application-facing DTO, but it must not duplicate the existing domain `AccountRegistrationResult` semantics or expose domain internals.
- Event delivery: `AccountRegistered` exists, but reliable delivery is outside this issue unless an Outbox transaction boundary is introduced separately.
- Plain password lifetime: the command necessarily carries the plain password at the application boundary. The handler must pass it only to the password hasher and never include it in domain objects, results, events, logs, or tests beyond fake input.

## 9. Explicit non-goals

Issue #12 implementation must not add:

- HTTP controller
- request/response API DTOs
- routing
- login use case
- refresh token/session model
- JWT issuing
- refresh cookies
- Symfony Security firewall changes
- admin approval use cases
- create system admin CLI command
- frontend code
- project/workflow/issue permissions
- Messenger/RabbitMQ dispatch from the handler
- Redis
- OpenSearch/Elasticsearch
- Outbox implementation unless already available and explicitly required
- public password reset
- email verification
- migrations or account persistence changes, unless a missing issue #12 dependency forces a small configuration binding for new adapters

## 10. Implementation readiness checklist

- [x] Mandatory docs were reviewed: `AGENTS.md`, `docs/design/account-domain-model.md`, `docs/design/authentication-api-strategy.md`.
- [x] ADR-0002 was reviewed for source placement decisions.
- [x] Relevant IdentityAccess Domain source was inspected.
- [x] Relevant IdentityAccess Application source was inspected.
- [x] Relevant IdentityAccess Account persistence source was inspected.
- [x] Relevant Account domain/result/event tests were inspected.
- [x] Relevant Account repository/persistence tests were inspected.
- [x] Existing domain registration behavior is sufficient for issue #12.
- [x] Existing repository port is sufficient for duplicate pre-check and save.
- [x] Existing database unique constraint is sufficient as the final duplicate-email guarantee.
- [ ] Application use-case classes need implementation.
- [ ] Password hasher application port needs implementation.
- [ ] Shared clock/id technical ports need implementation if still missing.
- [ ] Infrastructure adapters need implementation and possible service wiring.
- [ ] Application unit tests need implementation.
- [ ] Flush-time unique violation mapping needs a separate decision if issue #12 does not define the transaction boundary.
