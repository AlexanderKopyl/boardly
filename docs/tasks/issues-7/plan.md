# Issue #7 Plan: Implement Base Account Domain Model

## 1. Summary

Plan a narrow first implementation of the base `Account` domain model for `IdentityAccess`.

The later implementation should create the domain model, value objects, lifecycle events, explicit exceptions, repository port, result objects if needed by the chosen event-output style, and focused unit tests.

This planning task does not implement any PHP source, tests, mappings, migrations, controllers, handlers, or configuration.

## 2. Proposed implementation strategy

Implement issue #7 in small, reviewable steps:

1. Add value objects first, starting with shared `AccountId`.
2. Add `AccountStatus`.
3. Add explicit domain exceptions.
4. Add lifecycle event classes.
5. Add lifecycle result objects if the implementation follows the account design document's explicit event-output style.
6. Add the `Account` aggregate root with named constructors and lifecycle methods.
7. Add `AccountRepositoryInterface` in the application port layer.
8. Add fast unit tests for value objects, account creation, lifecycle transitions, invalid transitions, and event/result safety.

Keep all domain code independent from Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, HTTP, JWT, cookies, and framework services.

Do not add Doctrine attributes, mappings, migrations, Symfony validation constraints, Messenger dispatching, password hashing services, or refresh-session logic.

## 3. Exact files to create later

Future source files:

```text
src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php
src/Boardly/IdentityAccess/Domain/Model/Account.php
src/Boardly/IdentityAccess/Domain/ValueObject/Email.php
src/Boardly/IdentityAccess/Domain/ValueObject/PasswordHash.php
src/Boardly/IdentityAccess/Domain/ValueObject/AccountName.php
src/Boardly/IdentityAccess/Domain/ValueObject/AccountStatus.php
src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php
src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php
src/Boardly/IdentityAccess/Domain/Event/AccountRejected.php
src/Boardly/IdentityAccess/Domain/Event/AccountDisabled.php
src/Boardly/IdentityAccess/Domain/Exception/InvalidAccountStatusTransition.php
src/Boardly/IdentityAccess/Domain/Exception/AccountNotPendingApproval.php
src/Boardly/IdentityAccess/Domain/Exception/AccountAlreadyActive.php
src/Boardly/IdentityAccess/Domain/Exception/AccountAlreadyRejected.php
src/Boardly/IdentityAccess/Domain/Exception/AccountAlreadyDisabled.php
src/Boardly/IdentityAccess/Domain/Exception/InvalidEmail.php
src/Boardly/IdentityAccess/Domain/Exception/InvalidPasswordHash.php
src/Boardly/IdentityAccess/Domain/Exception/InvalidAccountName.php
src/Boardly/IdentityAccess/Domain/Exception/InvalidAccountStatus.php
src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
```

Possible future result-object files, depending on final implementation convention:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountResult.php
src/Boardly/IdentityAccess/Application/ApproveAccount/AccountApprovalResult.php
src/Boardly/IdentityAccess/Application/RejectAccount/AccountRejectionResult.php
src/Boardly/IdentityAccess/Application/DisableAccount/AccountDisableResult.php
```

If result objects are used directly by domain methods as suggested by the design document examples, keep them explicit and free of framework dependencies. Do not expose Doctrine entities or secrets from result objects.

Future test files:

```text
tests/Boardly/SharedKernel/Domain/ValueObject/AccountIdTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/EmailTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/PasswordHashTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/AccountNameTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/AccountStatusTest.php
tests/Boardly/IdentityAccess/Domain/Model/AccountCreationTest.php
tests/Boardly/IdentityAccess/Domain/Model/AccountLifecycleTest.php
tests/Boardly/IdentityAccess/Domain/Event/AccountEventTest.php
```

## 4. Exact files to inspect but not modify unless needed

Inspect during the later implementation phase:

```text
composer.json
phpunit.xml.dist
phpstan.neon
config/services.yaml
src/Kernel.php
src/Interfaces/Http/Controller/HealthCheckController.php
tests/Interfaces/Http/Controller/HealthCheckControllerTest.php
docs/design/account-domain-model.md
```

Do not modify these unless a concrete implementation blocker is discovered and the scope is explicitly accepted.

Do not inspect or modify `.env`, `.env.local`, secrets, private keys, SQL/database dumps, or production credentials.

## 5. Domain model design

`Account` should be a final aggregate root class without extending a generic aggregate base class.

Namespace:

```text
App\Boardly\IdentityAccess\Domain\Model
```

Responsibilities:

- hold identity, credentials, lifecycle state, global system-admin capability, and lifecycle timestamps;
- expose named constructors for registration and system-admin bootstrap;
- enforce its own lifecycle transitions;
- produce explicit lifecycle outputs/events through result objects or returned events;
- expose read methods needed by application handlers without exposing mutable internals.

Do not include:

- actor authorization checks;
- project/workflow/issue permissions;
- project memberships;
- refresh sessions;
- JWTs;
- cookies;
- password hashing or verification;
- persistence details.

## 6. Value object design

Prefer immutable final value objects.

Each value object should:

- validate at construction or named constructor;
- compare by value;
- expose explicit accessors for persistence/application boundaries;
- avoid Symfony Validator attributes;
- avoid Doctrine attributes;
- avoid framework dependencies.

`AccountId` belongs in `SharedKernel` because other bounded contexts will reference account identity without loading `Account`.

`Email`, `PasswordHash`, `AccountName`, and `AccountStatus` belong in `IdentityAccess` because they are part of account identity/lifecycle/credentials.

## 7. AccountStatus design

`AccountStatus` should accept only:

```text
pending_approval
active
rejected
disabled
```

Preferred API:

- named constructors for each state;
- `fromString()` for persistence boundaries;
- `value()` for persistence/output boundaries;
- equality method;
- state helper methods if useful.

Do not use arbitrary strings throughout `Account`.

## 8. Lifecycle transition design

Creation flows:

- `Account::register(...)`
- `Account::createSystemAdmin(...)`

Lifecycle methods:

- `approve(DateTimeImmutable $approvedAt)`
- `reject(DateTimeImmutable $rejectedAt)`
- `disable(DateTimeImmutable $disabledAt)`

Allowed first-scope transitions:

- `pending_approval -> active`
- `pending_approval -> rejected`
- `active -> disabled`

Rejected first-scope transitions:

- `pending_approval -> disabled`
- `active -> active`
- `active -> rejected`
- `active -> pending_approval`
- `rejected -> active`
- `rejected -> rejected`
- `rejected -> disabled`
- `rejected -> pending_approval`
- `disabled -> disabled`
- `disabled -> active`
- `disabled -> rejected`
- `disabled -> pending_approval`

Do not implement or plan `reopenApproval()` or `reactivate()` for issue #7. Treat those as future lifecycle expansion points from the broader design document.

## 9. Timestamp behavior

`register($createdAt)`:

- status: `pending_approval`
- `isSystemAdmin`: `false`
- `createdAt`: `$createdAt`
- `updatedAt`: `$createdAt`
- `approvedAt`: `null`
- `rejectedAt`: `null`
- `disabledAt`: `null`

`createSystemAdmin($createdAt)`:

- status: `active`
- `isSystemAdmin`: `true`
- `createdAt`: `$createdAt`
- `updatedAt`: `$createdAt`
- `approvedAt`: `$createdAt`
- `rejectedAt`: `null`
- `disabledAt`: `null`

`approve($approvedAt)`:

- requires current status `pending_approval`;
- status becomes `active`;
- `approvedAt` becomes `$approvedAt`;
- `rejectedAt` is cleared;
- `disabledAt` remains `null`;
- `updatedAt` becomes `$approvedAt`.

`reject($rejectedAt)`:

- requires current status `pending_approval`;
- status becomes `rejected`;
- `rejectedAt` becomes `$rejectedAt`;
- `approvedAt` remains `null`;
- `disabledAt` remains `null`;
- `updatedAt` becomes `$rejectedAt`.

`disable($disabledAt)`:

- requires current status `active`;
- status becomes `disabled`;
- `disabledAt` becomes `$disabledAt`;
- `approvedAt` remains as prior lifecycle metadata;
- `rejectedAt` remains `null`;
- `updatedAt` becomes `$disabledAt`.

## 10. Domain event design

Create first-scope event classes:

- `AccountRegistered`
- `AccountApproved`
- `AccountRejected`
- `AccountDisabled`

Events should be final immutable objects.

Events may include:

- `AccountId`
- relevant lifecycle timestamp
- resulting `AccountStatus` where useful
- `Email` only where there is a clear account-management need
- `isSystemAdmin` where useful for registration/bootstrap distinction

Events must not include:

- plain passwords
- password hashes unless a later use case explicitly requires them
- raw refresh tokens
- refresh token hashes
- JWTs
- cookies
- HTTP request data
- Doctrine entities

Do not add Messenger dispatching. Do not add outbox persistence in issue #7.

`Account` should not keep an internal mutable event buffer. Follow the design document's explicit result-object/event-output style.

## 11. Domain exception design

Use explicit exceptions for expected business failures.

Lifecycle exceptions:

- `InvalidAccountStatusTransition`
- `AccountNotPendingApproval`
- `AccountAlreadyActive`
- `AccountAlreadyRejected`
- `AccountAlreadyDisabled`

Value-object exceptions:

- `InvalidEmail`
- `InvalidPasswordHash`
- `InvalidAccountName`
- `InvalidAccountStatus`

Consider whether `InvalidAccountId` belongs under `SharedKernel` rather than `IdentityAccess` when implementing `AccountId`.

Do not throw HTTP exceptions, Doctrine exceptions, Symfony exceptions, or generic `RuntimeException` for expected domain failures.

## 12. Application port design

Create `AccountRepositoryInterface` under:

```text
src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
```

Initial method shape:

```text
save(Account $account): void
get(AccountId $id): Account
find(AccountId $id): ?Account
findByEmail(Email $email): ?Account
existsByEmail(Email $email): bool
countActiveSystemAdmins(): int
```

Dependencies:

- domain model/value objects;
- application/domain exceptions where needed.

No dependencies:

- Doctrine EntityManager;
- Symfony container;
- HTTP request/response;
- JWT/cookie/security classes;
- Redis;
- OpenSearch;
- RabbitMQ/Messenger.

`countActiveSystemAdmins()` supports future application-level last-active-system-admin protection. That cross-account invariant is not enforced inside `Account`.

## 13. Unit test plan

Value-object tests:

- `AccountId` accepts valid UUID and rejects invalid/empty values.
- `Email` trims, lowercases, compares normalized values, and rejects invalid input.
- `PasswordHash` accepts realistic Argon2id/bcrypt-shaped hashes, rejects empty input, rejects obvious plain-password-like values, preserves exact hash text, and does not leak rejected values in exception messages.
- `AccountName` trims, rejects empty values, accepts 100 characters, rejects 101 characters.
- `AccountStatus` accepts only the four allowed statuses and rejects unknown statuses.

Creation tests:

- `register()` creates pending, non-system-admin account with correct timestamps.
- `register()` produces `AccountRegistered` through the chosen explicit output style.
- `createSystemAdmin()` creates active system-admin account with `approvedAt` set to creation time.
- public registration cannot create a system admin or active account.

Valid lifecycle tests:

- pending account can be approved.
- pending account can be rejected.
- active account can be disabled.
- each method updates timestamps exactly as planned.
- each method produces the expected event through the chosen explicit output style.

Invalid lifecycle tests:

- cover every rejected transition listed in section 8.
- assert explicit domain exceptions.
- assert failed transitions do not mutate account state or timestamps.

Security/event tests:

- events/results do not expose plain passwords, password hashes, refresh tokens, JWTs, cookies, or HTTP data.
- domain classes do not require Symfony/Doctrine/Messenger/JWT/cookie services.

Quality gate:

```bash
composer test
composer phpstan
```

Targeted future run:

```bash
./vendor/bin/phpunit tests/Boardly/IdentityAccess tests/Boardly/SharedKernel
```

## 14. Composer/autoload impact

No Composer autoload change is expected.

Current `composer.json` already maps:

```json
"App\\": "src/"
```

and:

```json
"App\\Tests\\": "tests/"
```

Future issue #7 classes under `src/Boardly/...` and tests under `tests/Boardly/...` are covered by the existing mappings.

Do not modify `composer.json` for issue #7 unless implementation reveals a concrete blocker.

## 15. Symfony container impact

No Symfony service configuration change should be part of issue #7.

Risk: current service discovery scans `src/`, so later domain/value-object/event/result classes may be considered by Symfony autowiring. If that causes container issues, handle service exclusions as a separate configuration task or explicitly expand scope before modifying `config/services.yaml`.

Domain classes must not use Symfony attributes or service dependencies.

## 16. Doctrine impact

No Doctrine mapping, Doctrine attributes, XML/YAML mappings, repositories, entities, migrations, or database schema changes should be introduced in issue #7.

The relational database remains the future source of truth, but persistence implementation is out of scope for the base domain-model issue.

## 17. Security impact

Security-critical boundaries:

- `PasswordHash` stores only already-hashed values.
- `Account` never receives plain passwords.
- `Account` does not hash passwords.
- `Account` does not verify passwords.
- no raw refresh token or refresh token hash belongs in `Account`.
- no JWT, cookie, HTTP, or Symfony Security dependency belongs in `Account`.
- no project/workflow/issue permission belongs in `Account`.
- only active accounts may receive tokens later, but token issuance is out of scope here.

Avoid `PasswordHash::__toString()` unless there is a strong implementation reason. Prefer explicit accessors for persistence boundaries and avoid logging hash values.

## 18. Explicit non-goals

Do not implement:

- PHP code during this planning task;
- RefreshSession model;
- refresh token rotation;
- login handlers;
- refresh handlers;
- logout handlers;
- Symfony Security configuration;
- JWT library integration;
- Doctrine mappings;
- database migrations;
- HTTP controllers;
- API DTOs;
- frontend code;
- project permissions;
- workflow permissions;
- issue permissions;
- project memberships;
- Redis usage;
- OpenSearch/Elasticsearch usage;
- RabbitMQ/Symfony Messenger dispatching;
- password hashing service;
- outbox implementation;
- public password reset;
- OAuth, MFA, or SSO;
- `reopenApproval()`;
- `reactivate()`.

## 19. Risks and trade-offs

- First domain conventions: no domain classes exist yet, so issue #7 will establish conventions. Keep them simple and explicit.
- Event output style: result objects make events visible but require discipline in application handlers. Internal event buffers are explicitly deferred by the design document.
- Password hash validation: too strict a validator may reject future valid algorithms. Too loose a validator may accept plain text. Start with practical checks and tests.
- Broader design vs issue scope: the design document includes refresh sessions, login/refresh/logout, `reopenApproval()`, and `reactivate()`. Issue #7 must not absorb those.
- Application-level safety rules: self-disable and last-active-system-admin protection require multiple accounts and belong in future handlers, not inside `Account`.
- Symfony service discovery: future pure domain classes may require service exclusion cleanup, but that is configuration work and not part of this domain-model issue unless it blocks tests/container compilation.

## 20. Step-by-step implementation checklist for the later implementation phase

1. Re-read `docs/tasks/issues-7/analysis.md` and this plan.
2. Confirm no newer design document or ADR supersedes `docs/design/account-domain-model.md`.
3. Inspect current `src/Boardly` and `tests/Boardly` contents before creating files.
4. Create `src/Boardly/SharedKernel/Domain/ValueObject/`.
5. Implement `AccountId` and its tests.
6. Create `src/Boardly/IdentityAccess/Domain/ValueObject/`.
7. Implement `Email` and its tests.
8. Implement `PasswordHash` and its tests.
9. Implement `AccountName` and its tests.
10. Implement `AccountStatus` and its tests.
11. Create `src/Boardly/IdentityAccess/Domain/Exception/`.
12. Implement explicit value-object and lifecycle exceptions.
13. Create `src/Boardly/IdentityAccess/Domain/Event/`.
14. Implement `AccountRegistered`, `AccountApproved`, `AccountRejected`, and `AccountDisabled`.
15. Decide the narrow result-object location consistent with existing code at implementation time.
16. Implement only the result objects needed for account creation/lifecycle event output.
17. Create `src/Boardly/IdentityAccess/Domain/Model/`.
18. Implement `Account` with private/internal construction and named constructors.
19. Implement `approve()`, `reject()`, and `disable()` only.
20. Add account creation tests.
21. Add valid lifecycle transition tests.
22. Add invalid lifecycle transition tests.
23. Add event/result safety tests.
24. Create `src/Boardly/IdentityAccess/Application/Port/`.
25. Implement `AccountRepositoryInterface`.
26. Run targeted PHPUnit tests for `tests/Boardly/SharedKernel` and `tests/Boardly/IdentityAccess`.
27. Run the full test suite.
28. Run PHPStan.
29. Confirm no Doctrine mapping, migration, Symfony config, controller, handler, Messenger, JWT, cookie, refresh-session, or permission code was added.

## 21. Acceptance criteria checklist

- [ ] `AccountId` exists under `SharedKernel`.
- [ ] `IdentityAccess` source folders are created only as needed for issue #7 files.
- [ ] `Account` exists under `src/Boardly/IdentityAccess/Domain/Model/Account.php`.
- [ ] `Email`, `PasswordHash`, `AccountName`, and `AccountStatus` value objects exist.
- [ ] `Account` is treated as an aggregate root without a generic aggregate base class.
- [ ] `register()` creates pending, non-system-admin accounts.
- [ ] `createSystemAdmin()` creates active system-admin accounts.
- [ ] `approve()`, `reject()`, and `disable()` enforce only first-scope transitions.
- [ ] Invalid transitions are rejected by explicit domain exceptions.
- [ ] Timestamps follow the planned behavior.
- [ ] Domain events exist for registered, approved, rejected, and disabled lifecycle facts.
- [ ] `Account` does not internally record/pull domain events.
- [ ] Explicit result/event output style is used if events are produced by domain methods.
- [ ] `AccountRepositoryInterface` exists under `Application/Port`.
- [ ] Repository port depends only on domain/application-safe types.
- [ ] Value-object tests exist.
- [ ] Account creation tests exist.
- [ ] Valid lifecycle transition tests exist.
- [ ] Invalid lifecycle transition tests exist.
- [ ] Password hash security-boundary tests exist.
- [ ] No PHP implementation was done during the analysis/planning task.
- [ ] No tests were implemented during the analysis/planning task.
- [ ] No Composer config was changed.
- [ ] No Symfony config was changed.
- [ ] No Doctrine config or migrations were created.
- [ ] No controllers, handlers, JWT/security code, refresh-session logic, permissions, Redis, OpenSearch, or Messenger code was introduced.
- [ ] Domain model remains independent from Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, HTTP, JWT, cookies, and framework services.
