# Issue #39 Analysis: IdentityAccess Console Commands

## 1. Issue Summary

Issue: <https://github.com/AlexanderKopyl/boardly/issues/39>

Issue #39 adds operational Symfony Console entry points for IdentityAccess account lifecycle management:

```bash
php bin/console boardly:identity-access:create-system-admin <email> <plain-password> <name> --env=prod
php bin/console boardly:identity-access:approve-account <email> --env=prod
```

The required architecture is:

```text
ConsoleCommand
-> Application use case / handler
-> Domain method
-> Repository save
-> Outbox store
-> Transaction
```

Console commands must be thin transport adapters. They must not mutate Doctrine entities, issue SQL updates, duplicate domain lifecycle rules, print plain passwords, or print password hashes.

## 2. MemPalace Findings

Relevant MemPalace memory found:

- IdentityAccess uses `Account` as the domain entity name, not `User`.
- IdentityAccess owns account identity, credentials, account status, login eligibility, and authentication-related lifecycle rules.
- IdentityAccess domain code must not depend on Symfony Security, Doctrine, controllers, cookies, JWT libraries, or transport concerns.
- Account lifecycle statuses are `pending_approval`, `active`, `rejected`, and `disabled`.
- Public registration creates a `pending_approval` account.
- Admin approval activates an account.
- System-admin capability belongs to the account domain model.
- Application handlers validate and normalize input through value objects, hash passwords through `PasswordHasherInterface`, generate IDs through an ID generator port, use a clock port, save through `AccountRepositoryInterface`, and return safe application result objects.
- Reliable domain events must be stored in the transactional outbox in the same database transaction as the aggregate mutation.
- RabbitMQ/Messenger publishing must happen after commit through outbox infrastructure, not directly from domain entities, domain services, console commands, or handlers.

No memory conflict was found with the broad architecture. Current code is stronger evidence and shows one concrete implementation gap: `AccountApproved` exists but does not currently implement `DomainEvent`, while `OutboxInterface::store()` stores `DomainEvent` instances.

## 3. Current Repository Facts

### Domain model and lifecycle

- `src/Boardly/IdentityAccess/Domain/Model/Account.php`
  - `Account::register(...)` creates a pending, non-system-admin account and returns `AccountRegistrationResult`.
  - `Account::createSystemAdmin(...)` creates an active system-admin account, sets `approvedAt` to creation time, and returns `AccountRegistrationResult`.
  - `Account::approve(...)` changes a pending account to active and returns `AccountApprovalResult`.
  - `approve(...)` throws existing domain exceptions instead of exposing status mutation:
    - `AccountAlreadyActive`
    - `AccountNotPendingApproval`

- `src/Boardly/IdentityAccess/Domain/Result/AccountRegistrationResult.php`
  - Exposes `account()` and `event()`.

- `src/Boardly/IdentityAccess/Domain/Result/AccountApprovalResult.php`
  - Exposes `event()`.

- `src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php`
  - Implements `App\Boardly\SharedKernel\Domain\Event\DomainEvent`.
  - Exposes account id, email, system-admin flag, and registered timestamp.

- `src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php`
  - Exists and exposes account id plus approved timestamp.
  - Does not currently implement `DomainEvent`.

### Value objects

Existing value objects to reuse:

- `src/Boardly/IdentityAccess/Domain/ValueObject/Email.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/AccountName.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/PasswordHash.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/AccountStatus.php`
- `src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php`

### Application ports and infrastructure adapters

- `src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php`
  - `save(Account $account): void`
  - `get(AccountId $id): Account`
  - `find(AccountId $id): ?Account`
  - `findByEmail(Email $email): ?Account`
  - `existsByEmail(Email $email): bool`
  - `countActiveSystemAdmins(): int`

- `src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/Repository/DoctrineAccountRepository.php`
  - Implements account save, find, find by email, duplicate check, and active system-admin count.

- `src/Boardly/IdentityAccess/Application/Port/PasswordHasherInterface.php`
  - Existing password-hashing port.

- `src/Boardly/IdentityAccess/Infrastructure/Security/SymfonyPasswordHasher.php`
  - Implements `PasswordHasherInterface` and `PasswordVerifierInterface`.

- `src/Shared/Application/Port/IdGeneratorInterface.php`
  - Existing ID generator port.

- `src/Shared/Infrastructure/Identity/UuidGenerator.php`
  - Existing UUID v7 generator adapter.

- `src/Shared/Application/Port/ClockInterface.php`
  - Existing clock port.

- `src/Shared/Infrastructure/Time/SystemClock.php`
  - Existing UTC clock adapter.

### Transaction and outbox

- `src/Shared/Application/Transaction/TransactionalInterface.php`
  - Application-level transaction boundary.

- `src/Shared/Infrastructure/Doctrine/DoctrineTransactional.php`
  - Uses `EntityManagerInterface::wrapInTransaction(...)`.
  - This is important because Doctrine ORM flushes tracked entity changes before commit.

- `src/Shared/Application/Outbox/OutboxInterface.php`
  - Stores `list<DomainEvent>`.

- `src/Shared/Infrastructure/Outbox/DoctrineOutbox.php`
  - Inserts durable rows into `outbox_messages`.

- `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxEventSerializer.php`
  - Serializes `AccountRegistered` to `identity_access.account_registered`.

- `src/Shared/Infrastructure/Outbox/Command/PublishOutboxCommand.php`
  - Existing Symfony Console command example: `boardly:outbox:publish`.

### Service registration

- `config/services.yaml`
  - Uses autowire and autoconfigure.
  - Tags `OutboxEventSerializerInterface` with `boardly.outbox_event_serializer`.
  - Tags `OutboxMessageMapperInterface` with `boardly.outbox_message_mapper`.
  - Explicitly tags current command handlers on `command.bus`, including `RegisterAccountHandler`.
  - New application handlers should follow the same explicit `messenger.message_handler` tag pattern unless handler registration is refactored separately.

### Tests that can be mirrored

- `tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php`
  - Covers normalized email, password hashing, duplicate email, safe result shape, transaction usage, and save/outbox ordering.

- `tests/Boardly/IdentityAccess/Domain/Model/AccountCreationTest.php`
  - Covers `Account::createSystemAdmin(...)`.

- `tests/Boardly/IdentityAccess/Domain/Model/AccountApprovalTest.php`
  - Covers `Account::approve(...)` and invalid active-account approval.

- `tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php`
  - Console command testing example using `CommandTester`.

## 4. Current Registration Transaction Flow

Current registration flow is implemented in:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
```

The flow is:

```text
RegisterAccountCommand
-> Email::fromString(...)
-> AccountName::fromString(...)
-> AccountRepositoryInterface::existsByEmail(...)
-> PasswordHasherInterface::hash(...)
-> PasswordHash::fromString(...)
-> IdGeneratorInterface::generate(...)
-> AccountId::fromString(...)
-> ClockInterface::now()
-> Account::register(...)
-> AccountRepositoryInterface::save(...)
-> OutboxInterface::store([$domainResult->event()])
-> RegisterAccountResult
```

The full use-case body runs inside:

```text
TransactionalInterface::transactional(...)
```

The focused test `testAccountSaveAndOutboxStoreRunInsideTransactionInOrder()` verifies that `save` happens before `outbox` and both happen inside the same transaction.

Issue #39 should mirror this pattern for system-admin creation and account approval.

## 5. Existing Reusable Building Blocks

### Create system admin

Reusable pieces:

- `Account::createSystemAdmin(...)`
- `AccountRegistrationResult`
- `AccountRegistered`
- `Email`
- `AccountName`
- `PasswordHash`
- `AccountId`
- `AccountRepositoryInterface::existsByEmail(...)`
- `AccountRepositoryInterface::save(...)`
- `PasswordHasherInterface`
- `IdGeneratorInterface`
- `ClockInterface`
- `TransactionalInterface`
- `OutboxInterface`
- `EmailAlreadyRegistered`

### Approve account

Reusable pieces:

- `Account::approve(...)`
- `AccountApprovalResult`
- `AccountApproved`
- `Email`
- `AccountRepositoryInterface::findByEmail(...)`
- `AccountRepositoryInterface::save(...)`
- `ClockInterface`
- `TransactionalInterface`
- `OutboxInterface`
- `AccountNotFound`
- Domain lifecycle exceptions from `Account::approve(...)`

### Console adapters

Reusable patterns:

- `src/Shared/Infrastructure/Outbox/Command/PublishOutboxCommand.php` for Symfony Console structure.
- `tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php` for `CommandTester` usage.
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RegisterAccountController.php` for dispatching application commands through `CommandBusInterface`.
- `docs/architecture/application-bus-rules.md` says controllers, CLI commands, workers, and other delivery adapters should use bus abstractions when available.

## 6. Gaps

### Create system admin

Missing application classes:

```text
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminCommand.php
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminHandler.php
src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminResult.php
```

The handler should mirror `RegisterAccountHandler`, but call `Account::createSystemAdmin(...)` and return safe output:

```text
account id
email
status
isSystemAdmin
```

### Approve account

Missing application classes:

```text
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountCommand.php
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php
src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountResult.php
```

Approval-specific gap:

- `AccountApproved` is not currently a `DomainEvent`.
- `OutboxInterface::store()` expects `DomainEvent`.
- There is no `AccountApprovedOutboxEventSerializer`.
- There is no approval outbox message/mapper if approval records should be publishable by the existing outbox publisher.

This must be resolved during implementation to satisfy "store the produced domain event through the outbox" for account approval.

### Console adapters

Missing console classes:

```text
src/Boardly/IdentityAccess/Interfaces/Console/CreateSystemAdminConsoleCommand.php
src/Boardly/IdentityAccess/Interfaces/Console/ApproveAccountConsoleCommand.php
```

The commands should parse arguments and dispatch application commands. They should not inject repositories, Doctrine, password hashers, outbox, transaction, or domain services directly.

### Tests

Missing application tests:

```text
tests/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminHandlerTest.php
tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php
```

Missing console tests:

```text
tests/Boardly/IdentityAccess/Interfaces/Console/CreateSystemAdminConsoleCommandTest.php
tests/Boardly/IdentityAccess/Interfaces/Console/ApproveAccountConsoleCommandTest.php
```

Likely missing outbox tests if approval is made publishable:

```text
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializerTest.php
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php
```

## 7. Risks and Constraints

### No direct DB mutation from console commands

Console commands must not:

- inject Doctrine entity managers;
- mutate Doctrine entities directly;
- execute SQL updates;
- set `status`, `approved_at`, or `is_system_admin` manually;
- bypass `Account::createSystemAdmin(...)` or `Account::approve(...)`.

### No password or hash leakage

Console commands and result objects must not expose:

- plain password;
- password hash;
- tokens;
- cookies;
- secrets;
- environment values;
- raw aggregate dumps;
- Doctrine entities.

Failure output should be concise and safe. Avoid printing exception traces in normal command output.

### Duplicate email handling

Create system admin must reject duplicate normalized email through `AccountRepositoryInterface::existsByEmail(...)`, matching registration. The database unique constraint remains the final consistency guard.

### Invalid approval transitions

Approve account must rely on `Account::approve(...)` and preserve existing domain exceptions:

- approving an active account throws `AccountAlreadyActive`;
- approving a rejected or disabled account throws `AccountNotPendingApproval`;
- lifecycle consistency remains enforced by the aggregate.

The console command should not pre-check or duplicate these lifecycle rules.

### Transaction and outbox consistency

Both operations must write account state and outbox records in the same `TransactionalInterface::transactional(...)` block.

Approval has a current-code blocker: `AccountApproved` is not a `DomainEvent`, so it cannot be passed to `OutboxInterface::store()` without changing the event contract. Adding an approval outbox serializer is also required for durable storage.

### Outbox publishing consistency

If an `identity_access.account_approved` outbox record is introduced, the outbox publisher should be able to map it. Otherwise, `boardly:outbox:publish` can repeatedly fail when it reaches approval records.

### Service wiring

New application handlers should be registered consistently with existing handlers in `config/services.yaml`. The final implementation should verify that:

- both console commands appear in `php bin/console list boardly:identity-access --env=prod`;
- both application handlers are reachable through the command bus;
- new outbox serializer and mapper services are tagged through existing `_instanceof` rules.
