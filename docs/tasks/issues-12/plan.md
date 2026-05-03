# Issue #12 Plan: RegisterAccount Use Case

## 1. Ordered implementation steps

### Step 1: Add missing application and shared ports

Create:

```text
src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
src/Shared/Application/Port/ClockInterface.php
src/Shared/Application/Port/IdGeneratorInterface.php
```

Expected responsibilities:

- `PasswordHasherInterface::hash(string $plainPassword): string`
- `ClockInterface::now(): DateTimeImmutable`
- `IdGeneratorInterface::generate(): string`

Keep these as ports only. Do not reference Symfony, Doctrine, HTTP, JWT, cookies, or framework services.

Test strategy:

- No dedicated tests are required for empty interfaces.
- These ports will be exercised through `RegisterAccountHandlerTest`.

### Step 2: Add duplicate-email application exception

Create:

```text
src/Boardly/IdentityAccess/Application/Exception/EmailAlreadyRegistered.php
```

Expected behavior:

- explicit application exception for normalized duplicate email;
- no dependency on Doctrine or Symfony HTTP exceptions;
- safe message that does not include plain password or password hash.

Test strategy:

- Assert through handler tests that duplicate email throws `EmailAlreadyRegistered`.

### Step 3: Add RegisterAccount command and application result

Create:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountCommand.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php
```

`RegisterAccountCommand` should carry:

```text
email: string
plainPassword: string
name: string
```

`RegisterAccountResult` should expose only application-safe response data:

```text
accountId: string
email: string
name: string
status: string
```

Do not expose:

- `Account`
- Doctrine entity
- plain password
- password hash
- domain event object
- access token
- refresh token/cookie

Test strategy:

- Handler success test should assert result values.
- Reflection-style assertions can be added if useful to prove no password/hash/token accessors exist.

### Step 4: Add RegisterAccount handler

Create:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
```

Handler dependencies:

```text
AccountRepositoryInterface
PasswordHasherInterface
ClockInterface
IdGeneratorInterface
```

Required flow:

1. Build `Email` with `Email::fromString($command->email())`.
2. Build `AccountName` with `AccountName::fromString($command->name())`.
3. Call `AccountRepositoryInterface::existsByEmail($email)`.
4. Throw `EmailAlreadyRegistered` if the email exists.
5. Hash `$command->plainPassword()` through `PasswordHasherInterface`.
6. Wrap the returned hash with `PasswordHash::fromString(...)`.
7. Generate an id through `IdGeneratorInterface`.
8. Wrap the generated id with `AccountId::fromString(...)`.
9. Get current time through `ClockInterface`.
10. Call `Account::register($id, $email, $passwordHash, $name, $now)`.
11. Use the existing domain `AccountRegistrationResult` returned by `Account::register(...)`.
12. Save `$domainResult->account()` through `AccountRepositoryInterface::save(...)`.
13. Return application `RegisterAccountResult` mapped from the created account.

Do not:

- create an application `AccountRegistrationResult`;
- call `Account::createSystemAdmin(...)`;
- pass plain password to `Account`;
- dispatch Messenger/RabbitMQ messages;
- issue tokens;
- create refresh sessions;
- flush Doctrine directly unless a repository/transaction boundary already requires it.

Test strategy:

- Add focused application unit tests with fake repository, hasher, clock, and id generator.
- Assert operation order through fake state where useful: duplicate email should stop before hashing/id generation/save.

### Step 5: Add infrastructure password hasher adapter

Create:

```text
src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php
```

Expected behavior:

- implements `App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface`;
- depends on Symfony password-hashing infrastructure;
- returns a hash format accepted by `PasswordHash::fromString(...)`;
- does not expose Symfony hasher classes outside Infrastructure.

Likely Symfony integration direction:

- use `Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface`;
- request the configured password hasher by class/name;
- add service configuration only if autowiring/configuration does not select the intended hasher.

Files that may need modification:

```text
config/packages/security.yaml
config/services.yaml
```

Only change these if implementation requires explicit service binding. Do not add firewall/login/JWT configuration.

Test strategy:

- Prefer a focused unit test or small integration test only if adapter wiring is non-trivial.
- Do not use this real adapter in `RegisterAccountHandlerTest`.

### Step 6: Add shared infrastructure adapters for clock and id generation

Create if missing:

```text
src/Shared/Infrastructure/Time/SystemClock.php
src/Shared/Infrastructure/Identity/UuidGenerator.php
```

Expected behavior:

- `SystemClock` implements `ClockInterface` using `new DateTimeImmutable(...)`.
- `UuidGenerator` implements `IdGeneratorInterface` and returns a valid UUID string accepted by `AccountId::fromString(...)`.

Files that may need modification:

```text
config/services.yaml
```

Only add explicit service aliases if Symfony autowiring cannot bind interface dependencies to these implementations.

Test strategy:

- Unit-test `UuidGenerator` only if UUID generation logic is custom.
- Handler tests should use fakes, not the system clock or random generator.

### Step 7: Add application unit tests

Create:

```text
tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php
```

Cover:

- successful registration saves one pending non-system-admin account;
- success result contains id, normalized email, trimmed name, and `pending_approval`;
- success result does not expose plain password or password hash;
- password hasher receives the plain password and Account receives only `PasswordHash`;
- generated id and clock time are used for `Account::register(...)`;
- duplicate normalized email throws `EmailAlreadyRegistered`;
- duplicate email does not hash password, generate id, or save account;
- invalid email propagates the existing domain validation exception and does not save;
- invalid name propagates the existing domain validation exception and does not save;
- invalid hasher output propagates `InvalidPasswordHash` and does not save;
- public registration never creates a system-admin account;
- handler does not issue access tokens or refresh tokens by result shape.

Use fakes in the test namespace, for example:

```text
FakeAccountRepository
FakePasswordHasher
FakeClock
FakeIdGenerator
```

Do not use Doctrine, Symfony container, HTTP kernel, JWT libraries, refresh cookies, Redis, OpenSearch, Messenger, or RabbitMQ in these tests.

### Step 8: Decide how to document duplicate DB race handling

Do not add broad Doctrine exception mapping in the handler by default.

Current persistence behavior:

- repository `save(...)` does not flush;
- duplicate email unique violation appears at entity-manager flush time;
- the transaction/flush boundary is not part of this issue.

For issue #12 implementation, document that:

- `existsByEmail(...)` gives the explicit application pre-check;
- `accounts.email` unique constraint remains the final guarantee;
- mapping `UniqueConstraintViolationException` to `EmailAlreadyRegistered` belongs with the transaction/command execution boundary unless issue #12 is expanded.

If implementation discovers an existing transaction boundary before coding, add a small follow-up decision in the PR notes rather than changing repository semantics unexpectedly.

## 2. Exact files to create/modify per step

Create:

```text
src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
src/Boardly/IdentityAccess/Application/Exception/EmailAlreadyRegistered.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountCommand.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php
src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php
src/Shared/Application/Port/ClockInterface.php
src/Shared/Application/Port/IdGeneratorInterface.php
src/Shared/Infrastructure/Time/SystemClock.php
src/Shared/Infrastructure/Identity/UuidGenerator.php
tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php
```

Modify only if needed for service wiring:

```text
config/services.yaml
config/packages/security.yaml
```

Do not modify unless a concrete implementation dependency requires it:

```text
src/Boardly/IdentityAccess/Domain/Model/Account.php
src/Boardly/IdentityAccess/Domain/Result/AccountRegistrationResult.php
src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php
src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Repository/DoctrineAccountRepository.php
src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Entity/AccountEntity.php
src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Mapper/AccountMapper.php
migrations/
```

## 3. Test strategy per step

Port and exception steps:

- covered indirectly by handler tests;
- syntax/static analysis is enough unless behavior is added.

Command/result step:

- covered by handler success assertions;
- optional reflection assertions for forbidden accessors: `plainPassword`, `password`, `passwordHash`, `accessToken`, `refreshToken`, `cookie`, `account`, `entity`.

Handler step:

- primary coverage through fast application unit tests with fakes;
- no kernel boot;
- no Doctrine;
- no real password hashing;
- no HTTP/API assertions.

Infrastructure adapter steps:

- add minimal adapter tests only if service wiring or hasher selection is not obvious;
- keep adapter tests separate from handler tests.

Regression checks:

- existing Account domain tests should continue to pass;
- existing Account persistence tests should continue to pass;
- no new persistence integration tests are required for issue #12 unless adapter wiring changes persistence behavior.

## 4. Acceptance criteria mapped to issue #12

- `RegisterAccountCommand(email, plainPassword, name)` exists and is application-layer only.
- `RegisterAccountHandler` creates `Email` and `AccountName` value objects before registration.
- Handler calls `AccountRepositoryInterface::existsByEmail($email)`.
- Duplicate email pre-check throws `EmailAlreadyRegistered`.
- Handler hashes the plain password through `PasswordHasherInterface`.
- Handler wraps the returned hash in `PasswordHash`.
- Handler generates an `AccountId` through an id-generator port.
- Handler gets current time through a clock port.
- Handler calls `Account::register(...)`.
- Handler uses existing domain `Domain/Result/AccountRegistrationResult`.
- Handler saves through `AccountRepositoryInterface`.
- Handler returns application `RegisterAccountResult`.
- Application `RegisterAccountResult` does not expose Account aggregate or Doctrine entity.
- Application `RegisterAccountResult` does not contain plain password or password hash.
- Public registration creates `pending_approval`.
- Public registration never creates a system-admin account.
- Domain remains free of Symfony, Doctrine, password hashing service, clock, and id-generator dependencies.
- Handler depends on application/shared ports, not concrete infrastructure.
- Symfony password hashing is hidden behind an infrastructure adapter.
- Database unique constraint remains the final duplicate-email guarantee.
- No HTTP controller/API DTO/JWT/refresh-cookie/login/admin/frontend work is added.
- No Messenger/RabbitMQ dispatch, Redis, OpenSearch, or Outbox implementation is added.

## 5. Verification commands

Run focused syntax checks for new production classes:

```bash
php -l src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php
php -l src/Boardly/IdentityAccess/Application/Exception/EmailAlreadyRegistered.php
php -l src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountCommand.php
php -l src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
php -l src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php
php -l src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php
php -l src/Shared/Application/Port/ClockInterface.php
php -l src/Shared/Application/Port/IdGeneratorInterface.php
php -l src/Shared/Infrastructure/Time/SystemClock.php
php -l src/Shared/Infrastructure/Identity/UuidGenerator.php
```

Run focused tests:

```bash
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/RegisterAccount
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Domain
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine
```

Run full project checks:

```bash
composer test
composer phpstan
composer qa
```

Adjust exact PHP/composer binary paths to the local environment if needed.

## 6. What must not be changed

Do not change:

- `Account::register(...)` semantics;
- existing domain `AccountRegistrationResult` into an application DTO;
- account lifecycle statuses;
- public registration pending-approval behavior;
- public registration non-system-admin behavior;
- `AccountRepositoryInterface` unless a missing method is discovered by implementation time;
- Doctrine account persistence, mapper, migration, or repository semantics;
- Symfony firewall/login/authenticator/JWT configuration;
- HTTP controllers, routes, request DTOs, or response DTOs;
- refresh-session model or tables;
- frontend code;
- project/workflow/issue permission models;
- Messenger/RabbitMQ, Redis, OpenSearch, or Outbox infrastructure.

## 7. Open questions only if truly blocking

No blocking open questions for implementing the application use case.

Non-blocking follow-up decision:

- Where should flush-time `UniqueConstraintViolationException` be mapped to `EmailAlreadyRegistered` once the command transaction boundary exists?
