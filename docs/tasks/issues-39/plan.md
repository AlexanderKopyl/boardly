# Issue #39 Plan: IdentityAccess Console Commands

## 1. Proposed File Tree

Application layer:

```text
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/
  CreateSystemAdminCommand.php
  CreateSystemAdminHandler.php
  CreateSystemAdminResult.php

src/Boardly/IdentityAccess/Application/ApproveAccount/
  ApproveAccountCommand.php
  ApproveAccountHandler.php
  ApproveAccountResult.php
```

Console adapters:

```text
src/Boardly/IdentityAccess/Interfaces/Console/
  CreateSystemAdminConsoleCommand.php
  ApproveAccountConsoleCommand.php
```

Approval outbox support:

```text
src/Boardly/IdentityAccess/Infrastructure/Outbox/
  AccountApprovedOutboxEventSerializer.php
  AccountApprovedOutboxMessageMapper.php

src/Boardly/IdentityAccess/Infrastructure/Message/
  AccountApprovedMessage.php
```

Tests:

```text
tests/Boardly/IdentityAccess/Application/CreateSystemAdmin/
  CreateSystemAdminHandlerTest.php

tests/Boardly/IdentityAccess/Application/ApproveAccount/
  ApproveAccountHandlerTest.php

tests/Boardly/IdentityAccess/Interfaces/Console/
  CreateSystemAdminConsoleCommandTest.php
  ApproveAccountConsoleCommandTest.php

tests/Boardly/IdentityAccess/Infrastructure/Outbox/
  AccountApprovedOutboxEventSerializerTest.php
  AccountApprovedOutboxMessageMapperTest.php
```

Existing files likely needing updates during implementation:

```text
src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php
src/Boardly/IdentityAccess/Application/Exception/AccountNotFound.php
config/services.yaml
config/packages/messenger.yaml
```

## 2. Step-by-Step Implementation Plan

1. Add application use case classes for `CreateSystemAdmin`.
2. Add application use case classes for `ApproveAccount`.
3. Make approval events compatible with the existing transactional outbox.
4. Add thin Symfony Console adapters that dispatch application commands through `CommandBusInterface`.
5. Register new handlers and message routing consistently with existing config.
6. Add focused application tests.
7. Add focused console tests.
8. Add approval outbox serializer/mapper tests if approval records are introduced.
9. Run focused syntax, PHPUnit, phpstan, and console-list verification.

## 3. Application Layer Plan

### CreateSystemAdminCommand

Path:

```text
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminCommand.php
```

Shape:

```text
email: string
plainPassword: string
name: string
```

This command is an application command, not a Symfony Console command.

### CreateSystemAdminHandler

Path:

```text
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminHandler.php
```

Dependencies:

```text
AccountRepositoryInterface
PasswordHasherInterface
ClockInterface
IdGeneratorInterface
TransactionalInterface
OutboxInterface
```

Flow:

```text
TransactionalInterface::transactional(...)
-> Email::fromString($command->email())
-> AccountName::fromString($command->name())
-> AccountRepositoryInterface::existsByEmail($email)
-> throw EmailAlreadyRegistered on duplicate
-> PasswordHasherInterface::hash($command->plainPassword())
-> PasswordHash::fromString($hash)
-> AccountId::fromString(IdGeneratorInterface::generate())
-> ClockInterface::now()
-> Account::createSystemAdmin(...)
-> AccountRepositoryInterface::save($account)
-> OutboxInterface::store([$domainResult->event()])
-> CreateSystemAdminResult
```

Important constraints:

- Do not print, return, or store the plain password outside the hash operation.
- Do not return `PasswordHash`.
- Do not expose the `Account` aggregate or Doctrine entity in the result.
- Preserve `EmailAlreadyRegistered` behavior.

### CreateSystemAdminResult

Path:

```text
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminResult.php
```

Safe fields:

```text
accountId: string
email: string
status: string
isSystemAdmin: bool
```

Do not include:

```text
plainPassword
password
passwordHash
tokens
cookies
account aggregate
Doctrine entity
```

### ApproveAccountCommand

Path:

```text
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountCommand.php
```

Shape:

```text
email: string
```

### ApproveAccountHandler

Path:

```text
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php
```

Dependencies:

```text
AccountRepositoryInterface
ClockInterface
TransactionalInterface
OutboxInterface
```

Flow:

```text
TransactionalInterface::transactional(...)
-> Email::fromString($command->email())
-> AccountRepositoryInterface::findByEmail($email)
-> throw AccountNotFound if null
-> ClockInterface::now()
-> Account::approve($now)
-> AccountRepositoryInterface::save($account)
-> OutboxInterface::store([$domainResult->event()])
-> ApproveAccountResult
```

Important constraints:

- Do not manually set status or timestamps.
- Do not pre-duplicate domain lifecycle checks in the handler or console adapter.
- Preserve domain exceptions from `Account::approve(...)`.
- Add a safe `AccountNotFound` path for missing email. Current `AccountNotFound` only has `withId(...)`; implementation can add `withEmail(Email $email)` with a safe generic message.

### ApproveAccountResult

Path:

```text
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountResult.php
```

Safe fields:

```text
accountId: string
email: string
status: string
approvedAt: DateTimeImmutable
```

Do not include:

```text
passwordHash
tokens
cookies
account aggregate
Doctrine entity
```

### Approval Outbox Compatibility

Current gap:

```text
AccountApproved does not implement DomainEvent.
OutboxInterface::store() accepts list<DomainEvent>.
```

Implementation should:

1. Update `AccountApproved` to implement `DomainEvent`.
2. Add `AccountApprovedOutboxEventSerializer`.
3. Use event type `identity_access.account_approved`.
4. Use aggregate type `identity_access.account`.
5. Include safe payload only:

```text
account_id
approved_at
```

Recommended to keep publisher behavior complete:

1. Add `AccountApprovedMessage`.
2. Add `AccountApprovedOutboxMessageMapper`.
3. Add Messenger routing for `AccountApprovedMessage`.

## 4. Console Layer Plan

### CreateSystemAdminConsoleCommand

Path:

```text
src/Boardly/IdentityAccess/Interfaces/Console/CreateSystemAdminConsoleCommand.php
```

Symfony command:

```text
boardly:identity-access:create-system-admin
```

Arguments:

```text
email
plain-password
name
```

Dependencies:

```text
CommandBusInterface
```

Flow:

```text
read arguments
-> dispatch CreateSystemAdminCommand
-> assert result is CreateSystemAdminResult
-> print concise safe success output
-> return Command::SUCCESS
```

Failure behavior:

```text
catch Throwable
-> print concise safe error message
-> return Command::FAILURE
```

Output may include:

```text
account id
normalized email
status
isSystemAdmin
```

Output must not include:

```text
plain password
password hash
tokens
secrets
environment values
exception trace
raw command object
raw aggregate
Doctrine entity
```

### ApproveAccountConsoleCommand

Path:

```text
src/Boardly/IdentityAccess/Interfaces/Console/ApproveAccountConsoleCommand.php
```

Symfony command:

```text
boardly:identity-access:approve-account
```

Arguments:

```text
email
```

Dependencies:

```text
CommandBusInterface
```

Flow:

```text
read email argument
-> dispatch ApproveAccountCommand
-> assert result is ApproveAccountResult
-> print concise safe success output
-> return Command::SUCCESS
```

Output may include:

```text
account id
normalized email
status
approved timestamp
```

Output must not include credential, token, secret, entity, or aggregate internals.

## 5. Service Registration Plan

Update `config/services.yaml` during implementation:

```yaml
App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminHandler:
    tags:
        - { name: messenger.message_handler, bus: command.bus }

App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountHandler:
    tags:
        - { name: messenger.message_handler, bus: command.bus }
```

The console commands should use `#[AsCommand]` and rely on autoconfigure.

Outbox event serializers and message mappers should rely on existing `_instanceof` tags:

```text
App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface
App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface
```

If `AccountApprovedMessage` is added, update `config/packages/messenger.yaml` routing consistently with `AccountRegisteredMessage`.

## 6. Testing Plan

### Application tests

Add:

```text
tests/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminHandlerTest.php
tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php
```

`CreateSystemAdminHandlerTest` should cover:

- successful system-admin creation saves an active system-admin account;
- normalized email is returned;
- duplicate email throws `EmailAlreadyRegistered`;
- password hashing happens before save;
- plain password is passed only to hasher test double;
- returned result does not expose password/hash/account/entity;
- account save and outbox store run inside the same transaction and in order;
- stored outbox event is `AccountRegistered` with `isSystemAdmin=true`.

`ApproveAccountHandlerTest` should cover:

- successful pending account approval;
- missing account by normalized email throws `AccountNotFound`;
- active account approval propagates `AccountAlreadyActive`;
- rejected or disabled account approval propagates `AccountNotPendingApproval`;
- returned result does not expose unsafe data;
- account save and outbox store run inside the same transaction and in order;
- stored outbox event is `AccountApproved`.

### Console tests

Add:

```text
tests/Boardly/IdentityAccess/Interfaces/Console/CreateSystemAdminConsoleCommandTest.php
tests/Boardly/IdentityAccess/Interfaces/Console/ApproveAccountConsoleCommandTest.php
```

Use `Symfony\Component\Console\Tester\CommandTester`, mirroring:

```text
tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php
```

Console tests should cover:

- command dispatches the expected application command;
- successful command returns `Command::SUCCESS`;
- failing command returns `Command::FAILURE` or another non-zero code;
- output contains expected safe success fields;
- output does not contain plain password;
- output does not contain password hash;
- output does not contain tokens, secrets, environment values, raw aggregate data, or Doctrine entity data.

### Outbox tests

If approval outbox support is added, add:

```text
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializerTest.php
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php
```

These should mirror `AccountRegistered` serializer/mapper tests and verify:

- event type is `identity_access.account_approved`;
- aggregate type is `identity_access.account`;
- payload contains only `account_id` and `approved_at`;
- invalid payloads are rejected safely;
- aggregate id and payload account id are consistent.

## 7. Focused Verification Commands For Later

Syntax checks:

```bash
php -l src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminCommand.php
php -l src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminHandler.php
php -l src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminResult.php
php -l src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountCommand.php
php -l src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php
php -l src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountResult.php
php -l src/Boardly/IdentityAccess/Interfaces/Console/CreateSystemAdminConsoleCommand.php
php -l src/Boardly/IdentityAccess/Interfaces/Console/ApproveAccountConsoleCommand.php
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializer.php
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapper.php
php -l src/Boardly/IdentityAccess/Infrastructure/Message/AccountApprovedMessage.php
```

Focused PHPUnit:

```bash
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/CreateSystemAdmin
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/ApproveAccount
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Console
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Outbox
```

Console visibility:

```bash
php bin/console list boardly:identity-access --env=prod
```

Static analysis and full project checks:

```bash
php /opt/homebrew/bin/composer phpstan
php /opt/homebrew/bin/composer test
```

## 8. Open Questions and Blockers

No product-scope blocker found.

Implementation blocker to resolve:

- `AccountApproved` must become a `DomainEvent`, and an approval outbox serializer must be added, before `ApproveAccountHandler` can satisfy the issue requirement to store the produced approval event through `OutboxInterface`.

Implementation decision to make:

- Add only an approval outbox event serializer, or also add `AccountApprovedMessage` plus mapper/routing so `boardly:outbox:publish` can publish approval records cleanly. The safer implementation is to add the message and mapper now.

Service wiring to verify:

- New handlers must be reachable through `CommandBusInterface`.
- New console commands must appear under `boardly:identity-access`.
- New approval outbox serializer and mapper must be registered by existing tags.
