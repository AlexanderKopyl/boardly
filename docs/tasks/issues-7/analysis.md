# Issue #7 Analysis: Implement Base Account Domain Model

## 1. Issue summary

GitHub issue #7 asks for a first implementation plan for the base `Account` domain model in the `IdentityAccess` bounded context.

This task is analysis and planning only. No PHP source, tests, Doctrine mappings, migrations, Symfony configuration, controllers, security integration, refresh-session logic, or frontend/API DTOs are implemented by this task.

The planned model covers:

- `Account` as the owner of account identity, lifecycle, credentials, and global system-admin capability.
- `AccountId`, `Email`, `PasswordHash`, `AccountName`, and `AccountStatus` value objects.
- first-scope lifecycle methods: `register()`, `createSystemAdmin()`, `approve()`, `reject()`, and `disable()`.
- first-scope domain events, explicit domain/application exceptions, the account repository port, result objects, and focused tests.

## 2. Required documents reviewed

Reviewed:

- `AGENTS.md`
- `docs/design/account-domain-model.md`

Also inspected narrowly relevant repository structure:

- `composer.json`
- `src/Interfaces/Http/Controller/HealthCheckController.php`
- `tests/Interfaces/Http/Controller/HealthCheckControllerTest.php`
- current `src/` and `tests/` file/directory structure
- existing task documentation style in `docs/tasks/issues-1/analysis.md` and `docs/tasks/issues-1/plan.md`

Sensitive files such as `.env`, `.env.local`, secrets, private keys, SQL/database dumps, and production credentials were not read.

## 3. Relevant findings from docs/design/account-domain-model.md

`IdentityAccess` owns account identity, account lifecycle, credentials, global system-admin capability, and authentication-session lifecycle.

For issue #7, only the `Account` portion is in scope. The document defines `Account` as:

```text
identity + lifecycle + credentials + global system-admin capability
```

`Account` owns only:

- `AccountId`
- `Email`
- `PasswordHash`
- `AccountName`
- `AccountStatus`
- `isSystemAdmin`
- `createdAt`
- `updatedAt`
- `approvedAt`
- `rejectedAt`
- `disabledAt`

`Account` must not own:

- project memberships
- project roles or project permissions
- issue permissions
- workflow permissions
- allowed workflow transitions
- notification settings
- preferences
- refresh tokens or refresh sessions

Initial statuses are:

- `pending_approval`
- `active`
- `rejected`
- `disabled`

The design document allows `reopenApproval()` and `reactivate()` in the broader lifecycle model, but says they can be deferred if not needed by the first milestone. The issue explicitly excludes planning those methods unless required, so this analysis keeps them deferred.

The document also states that `Account` should not internally store recorded domain events. Preferred first-milestone style is explicit result objects carrying produced events.

## 4. Relevant findings from AGENTS.md

Boardly is a Jira-like project/task/workflow management system, not a CRM.

For IdentityAccess/account work, `AGENTS.md` points to:

- `docs/design/account-domain-model.md`
- `docs/design/authentication-api-strategy.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`

The user explicitly limited required reading for this task to `AGENTS.md` and `docs/design/account-domain-model.md`, with optional documents only if rules were unclear. The account design document was sufficient for this issue scope.

Relevant operating constraints from `AGENTS.md`:

- `IdentityAccess` belongs under `src/Boardly/IdentityAccess/`.
- primary domain entity name is `Account`.
- account statuses are `pending_approval`, `active`, `rejected`, and `disabled`.
- account owns identity, lifecycle, credentials, and global system-admin capability only.
- account must not own project permissions, memberships, workflow permissions, issue permissions, refresh sessions, or frontend/session state.
- refresh tokens are not returned in JSON and raw refresh tokens are never stored.
- dynamic permissions must not be put into JWT claims.
- reliable event delivery uses the outbox later; domain entities must not dispatch Messenger/RabbitMQ directly.

## 5. Optional documents reviewed and why, if any

No optional ADRs or design documents were read.

Class placement and lifecycle rules were clear from `AGENTS.md` and `docs/design/account-domain-model.md`. `docs/adr/0002-use-boardly-context-based-source-structure.md`, `docs/design/authentication-api-strategy.md`, and `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` were therefore not needed for this planning pass.

Sequential thinking was used to resolve the trade-offs around aggregate-root treatment, domain event recording style, first-scope lifecycle transitions, and optional document usage.

## 6. Current repository structure observations

`src/Boardly` exists.

Existing Boardly context skeletons:

```text
src/Boardly/Issues/
src/Boardly/Projects/
src/Boardly/SharedKernel/
src/Boardly/Workflow/
```

`src/Boardly/IdentityAccess` does not currently exist.

`src/Boardly/SharedKernel/Domain` exists, but `src/Boardly/SharedKernel/Domain/ValueObject` does not currently exist.

`src/Shared` exists with:

```text
src/Shared/Application/
src/Shared/Domain/
src/Shared/Infrastructure/
```

Current PHP source files are minimal:

- `src/Kernel.php`
- `src/Interfaces/Http/Controller/HealthCheckController.php`

Current tests are minimal:

- `tests/bootstrap.php`
- `tests/Interfaces/Http/Controller/HealthCheckControllerTest.php`

`composer.json` maps `App\\` to `src/` and `App\\Tests\\` to `tests/`, so future classes under `src/Boardly/...` and tests under `tests/Boardly/...` do not require Composer autoload changes.

## 7. Existing conventions for domain models, value objects, events, exceptions, repositories, results, and tests

No implemented Boardly domain models, value objects, domain events, domain exceptions, repositories, or result objects were found under `src/Boardly` or `src/Shared`.

There are no existing base classes or interfaces for:

- aggregate roots
- domain events
- value objects
- domain exceptions
- repositories
- result objects

Current code conventions observable from existing PHP files:

- strict types are enabled with `declare(strict_types=1);`
- classes use `final` where applicable
- namespaces follow the `App\...` PSR-4 mapping
- PHPUnit tests live under `App\Tests\...`
- the existing HTTP test uses PHPUnit/Symfony assertions, but no domain test convention exists yet

For issue #7, the plan should introduce simple, explicit classes without inventing generic base classes.

## 8. Proposed Account aggregate/model boundary

`Account` should be treated as the aggregate root for this first version, even though no aggregate base class should be introduced.

Reasoning:

- the design document calls out an `Account` aggregate boundary;
- `Account` owns lifecycle state and must enforce lifecycle invariants;
- other contexts may reference `AccountId`, but must not depend on the `Account` model;
- refresh sessions, project memberships, permissions, JWTs, cookies, and frontend session state remain outside the aggregate.

The first implementation should use a private constructor plus named constructors:

- `Account::register(...)`
- `Account::createSystemAdmin(...)`

Lifecycle behavior should be methods on `Account`:

- `approve(...)`
- `reject(...)`
- `disable(...)`

Actor authorization, self-disable protection, last active system-admin protection, email uniqueness, persistence, transactions, and outbox storage belong to application/infrastructure work, not to the `Account` domain model itself.

## 9. Proposed value objects and invariants

`AccountId`

- lives in `src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php`;
- wraps a UUID string for the first milestone;
- rejects empty and invalid UUID values;
- compares by value;
- exposes an explicit string accessor for persistence and integration boundaries.

`Email`

- lives in `src/Boardly/IdentityAccess/Domain/ValueObject/Email.php`;
- trims and lowercases input before storing/comparison;
- validates basic email format;
- rejects empty or invalid values;
- does not check global uniqueness.

`PasswordHash`

- lives in `src/Boardly/IdentityAccess/Domain/ValueObject/PasswordHash.php`;
- stores an already-created password hash only;
- rejects empty and whitespace-only values;
- should reject obvious plain-password-like values;
- should accept realistic hash formats such as Argon2id and bcrypt-shaped values;
- must preserve the hash exactly and must not trim/lowercase/normalize credential material after validation;
- must not hash or verify passwords;
- should avoid exposing secrets through exception messages or accidental logging.

`AccountName`

- lives in `src/Boardly/IdentityAccess/Domain/ValueObject/AccountName.php`;
- trims input;
- rejects empty values;
- enforces maximum length of 100 characters;
- compares by value.

`AccountStatus`

- lives in `src/Boardly/IdentityAccess/Domain/ValueObject/AccountStatus.php`;
- supports only `pending_approval`, `active`, `rejected`, and `disabled`;
- should provide explicit named constructors or enum-like factories for those states;
- should provide comparison helpers such as `isPendingApproval()`, `isActive()`, `isRejected()`, and `isDisabled()` if useful.

## 10. Proposed lifecycle rules and transition constraints

First-scope allowed transitions:

- `pending_approval -> active` through `approve()`
- `pending_approval -> rejected` through `reject()`
- `active -> disabled` through `disable()`

Creation flows:

- `register()` creates `pending_approval`, non-system-admin accounts.
- `createSystemAdmin()` creates `active`, system-admin accounts.

Exact invalid transitions that must be rejected in issue #7:

- `pending_approval -> disabled`
- `active -> active` through calling `approve()` again
- `active -> rejected`
- `active -> pending_approval`
- `rejected -> active`
- `rejected -> rejected` through calling `reject()` again
- `rejected -> disabled`
- `rejected -> pending_approval` because `reopenApproval()` is deferred for issue #7
- `disabled -> disabled` through calling `disable()` again
- `disabled -> active` because `reactivate()` is deferred for issue #7
- `disabled -> rejected`
- `disabled -> pending_approval`

The broader design document allows `rejected -> pending_approval` and `disabled -> active` through future methods, but this issue should not plan those methods as implementation work.

Timestamp behavior:

- `register($createdAt)` sets `createdAt` and `updatedAt` to `$createdAt`; sets `approvedAt`, `rejectedAt`, and `disabledAt` to `null`.
- `createSystemAdmin($createdAt)` sets `createdAt`, `updatedAt`, and `approvedAt` to `$createdAt`; sets `rejectedAt` and `disabledAt` to `null`.
- `approve($approvedAt)` sets status to `active`, sets `approvedAt` to `$approvedAt`, clears `rejectedAt`, leaves `disabledAt` null, and sets `updatedAt` to `$approvedAt`.
- `reject($rejectedAt)` sets status to `rejected`, sets `rejectedAt` to `$rejectedAt`, leaves `approvedAt` and `disabledAt` null, and sets `updatedAt` to `$rejectedAt`.
- `disable($disabledAt)` sets status to `disabled`, sets `disabledAt` to `$disabledAt`, keeps prior `approvedAt` as lifecycle metadata, keeps `rejectedAt` null, and sets `updatedAt` to `$disabledAt`.

## 11. Proposed domain events

First-scope domain event classes:

- `AccountRegistered`
- `AccountApproved`
- `AccountRejected`
- `AccountDisabled`

Events should represent business facts and contain safe data only, such as:

- `AccountId`
- `Email` where needed for account-management workflows
- `AccountStatus` where useful
- lifecycle timestamp
- `isSystemAdmin` where needed for `AccountRegistered`

Events must not contain:

- plain passwords
- password hashes unless a specific future use case proves it is needed
- raw refresh tokens
- refresh token hashes
- JWTs
- cookies
- HTTP request data
- Doctrine entities

`Account` should not record events internally with `recordThat()`/`pullDomainEvents()` in this first version. Domain operations should return explicit result objects carrying produced event instances for later application/outbox handling.

## 12. Proposed domain exceptions

Expected domain exceptions should be explicit and not generic `RuntimeException`.

Recommended domain exceptions for issue #7:

- `InvalidAccountStatusTransition`
- `AccountNotPendingApproval`
- `AccountAlreadyActive`
- `AccountAlreadyRejected`
- `AccountAlreadyDisabled`

Value-object validation exceptions may either be specific per value object or use narrowly named exceptions under `Domain/Exception`, for example:

- `InvalidAccountId`
- `InvalidEmail`
- `InvalidPasswordHash`
- `InvalidAccountName`
- `InvalidAccountStatus`

Application-level exceptions should be planned separately from domain lifecycle exceptions:

- `AccountNotFound`
- `EmailAlreadyRegistered`
- `AccountNotActive`
- `CannotDisableOwnAccount`
- `CannotDisableLastActiveSystemAdmin`

For issue #7, application-level exception classes may be planned but should not be overused inside the domain model.

## 13. Proposed application port AccountRepositoryInterface

`AccountRepositoryInterface` should live at:

```text
src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
```

It should depend only on domain/application-safe types:

- `Account`
- `AccountId`
- `Email`
- application/domain exceptions such as `AccountNotFound`

Initial method direction from the design document:

```text
save(Account $account): void
get(AccountId $id): Account
find(AccountId $id): ?Account
findByEmail(Email $email): ?Account
existsByEmail(Email $email): bool
countActiveSystemAdmins(): int
```

The interface must not depend on Doctrine, Symfony services, HTTP requests, JWTs, cookies, Redis, OpenSearch, RabbitMQ, or Messenger.

The repository port belongs in Application because use cases orchestrate loading, uniqueness checks, cross-account invariants, persistence, and transaction boundaries.

## 14. Testing impact

Required future tests before implementation is accepted:

- value-object tests for `AccountId`
- value-object tests for `Email`
- value-object tests for `PasswordHash`
- value-object tests for `AccountName`
- value-object tests for `AccountStatus`
- account creation tests for `register()`
- account creation tests for `createSystemAdmin()`
- valid lifecycle transition tests for `approve()`, `reject()`, and `disable()`
- invalid lifecycle transition tests for every first-scope forbidden transition
- domain event/result tests proving the expected event is produced and no secret material is exposed
- repository interface placement/dependency review, likely through static analysis rather than behavior tests

Suggested future test locations:

```text
tests/Boardly/SharedKernel/Domain/ValueObject/AccountIdTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/EmailTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/PasswordHashTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/AccountNameTest.php
tests/Boardly/IdentityAccess/Domain/ValueObject/AccountStatusTest.php
tests/Boardly/IdentityAccess/Domain/Model/AccountCreationTest.php
tests/Boardly/IdentityAccess/Domain/Model/AccountLifecycleTest.php
```

## 15. Security boundaries

`PasswordHash` is security-sensitive.

It must forbid plain-password storage by:

- accepting only already-created hash values;
- rejecting empty and whitespace-only values;
- rejecting obvious plain-password-like input;
- preserving hash strings exactly after validation;
- not hashing or verifying passwords;
- not using Symfony PasswordHasher inside the domain;
- not exposing rejected secret-like input in exception messages;
- avoiding accidental stringification where possible.

`Account` must not issue tokens, create refresh sessions, store refresh sessions, store raw refresh tokens, store refresh token hashes, store JWT claims, or decide project/workflow/issue permissions.

Only active accounts may receive access or refresh tokens, but token issuance is future application/infrastructure work and is out of scope for this issue.

## 16. Explicit non-goals

Out of scope:

- implementing PHP code in this task
- `RefreshSession` model
- refresh token rotation
- login handlers
- refresh handlers
- logout handlers
- Symfony Security configuration
- JWT library integration
- Doctrine mappings
- database migrations
- HTTP controllers
- API DTOs
- frontend code
- project permissions
- workflow permissions
- issue permissions
- project memberships
- Redis usage
- OpenSearch/Elasticsearch usage
- RabbitMQ/Symfony Messenger dispatching
- outbox implementation
- password hashing service implementation
- public self-service password reset
- OAuth, MFA, or SSO
- `reopenApproval()` and `reactivate()` for issue #7

## 17. Risks

- The account design document includes broader lifecycle methods and refresh-session material. Issue #7 must stay narrowed to the base `Account` model and not pull in future authentication/session work.
- There are no existing domain-model conventions in code, so the first implementation will set precedent. Avoid generic base classes unless a concrete need appears.
- `config/services.yaml` scans `src/`, so later PHP domain classes may be autowired as services unless Symfony service configuration is adjusted in a separate task. This planning task does not modify configuration.
- Password hash validation can become too strict and reject legitimate hash algorithms. Keep validation practical: reject obvious plain text, but do not tie the domain permanently to one Symfony hasher format.
- Domain events without internal recording require clear result-object conventions so application handlers cannot accidentally ignore events.
- Cross-account safety rules such as self-disable and last active system-admin protection are not aggregate invariants; they must be enforced later in application use cases.

## 18. Open questions

- Should lifecycle result objects live under `Application/*Result.php`, use use-case-specific subdirectories, or use another convention once application handlers are introduced? No existing repository convention exists yet.
- What exact exception naming pattern should be preferred for value-object validation errors when implementation starts?
- Should domain events include normalized `Email`, or should most lifecycle events carry only `AccountId` and timestamps to minimize sensitive data exposure?
- Should `PasswordHash` support only PHC-style hashes plus bcrypt initially, or use a looser "not obvious plain text" rule to avoid rejecting future algorithms?
- Should service-autowiring exclusions for domain/value-object/event/result classes be handled in issue #7 implementation or deferred to the first infrastructure/configuration task?
