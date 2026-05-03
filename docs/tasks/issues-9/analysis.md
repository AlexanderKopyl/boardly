# Issue #9 Analysis: Account Persistence

## 1. Issue summary

Issue #9 is the planning step for implementing persistent storage for the `Account` aggregate in the `IdentityAccess` bounded context.

The expected production implementation is a Doctrine-backed persistence adapter for the application repository port, plus an `accounts` table that stores account identity, credential hash, lifecycle state, system-admin capability, lifecycle timestamps, and optimistic-locking version.

This task is analysis and planning only. No production code, migrations, mappings, tests, configuration, or composer files should be changed as part of this task.

## 2. Dependency status on issue #7

Issue #9 depends on issue #7 because persistence needs the Account domain model and repository port to exist first.

Current dependency status: satisfied enough to plan and implement issue #9, with one interface alignment required before persistence work begins.

Existing issue #7 artifacts found:

- `src/Boardly/IdentityAccess/Domain/Model/Account.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/Email.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/PasswordHash.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/AccountName.php`
- `src/Boardly/IdentityAccess/Domain/ValueObject/AccountStatus.php`
- `src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php`
- lifecycle events under `src/Boardly/IdentityAccess/Domain/Event/`
- lifecycle result objects under `src/Boardly/IdentityAccess/Domain/Result/`
- `src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php`
- related domain tests under `tests/Boardly/IdentityAccess/Domain/` and `tests/Boardly/SharedKernel/Domain/`

The current branch is not blocked on missing Account domain code. It is blocked from direct issue #9 implementation until the repository port is aligned with the expected issue #9 API.

Branch-name verification was not usable in this environment because `git branch --show-current` failed due a local Xcode command-line-tools path error. The dependency assessment is based on the current working tree contents.

## 3. Existing source/config structure relevant to persistence

Relevant source structure:

```text
src/Boardly/IdentityAccess/
├── Application/
│   └── Port/
│       └── AccountRepositoryInterface.php
└── Domain/
    ├── Event/
    ├── Exception/
    ├── Model/
    ├── Result/
    └── ValueObject/

src/Boardly/SharedKernel/
└── Domain/
    ├── Exception/
    └── ValueObject/
        └── AccountId.php
```

No `IdentityAccess/Infrastructure` persistence implementation exists yet.

Doctrine config currently exists in:

- `config/packages/doctrine.yaml`
- `config/packages/doctrine_migrations.yaml`

Current Doctrine ORM config uses attribute auto-mapping over `src`:

```yaml
doctrine:
    orm:
        auto_mapping: true
        mappings:
            App:
                type: attribute
                dir: '%kernel.project_dir%/src'
                prefix: 'App'
```

Migrations are configured under `migrations`, and no migration files exist yet beyond `.gitignore`.

Doctrine packages are present in `composer.json`:

- `doctrine/dbal`
- `doctrine/doctrine-bundle`
- `doctrine/doctrine-migrations-bundle`
- `doctrine/orm`

## 4. Current AccountRepositoryInterface API vs issue #9 expected API

Current interface:

```php
interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function findById(AccountId $id): ?Account;

    public function findByEmail(Email $email): ?Account;
}
```

Expected issue #9 API:

```php
interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function get(AccountId $id): Account;

    public function find(AccountId $id): ?Account;

    public function findByEmail(Email $email): ?Account;

    public function existsByEmail(Email $email): bool;

    public function countActiveSystemAdmins(): int;
}
```

Required alignment:

- Replace or adapt `findById(AccountId $id): ?Account` to `find(AccountId $id): ?Account`.
- Add `get(AccountId $id): Account` for required account lookups.
- Add `existsByEmail(Email $email): bool` for registration/application uniqueness checks.
- Add `countActiveSystemAdmins(): int` for last-active-system-admin safety checks.
- Define the missing-account exception used by `get()` before implementing the adapter.

## 5. Doctrine mapping strategy comparison

### A. Doctrine attributes directly on Domain Account

Pros:

- Lowest number of files.
- Familiar Symfony/Doctrine workflow.
- Works with the current broad attribute mapping style.

Cons:

- Adds Doctrine attributes to the domain aggregate.
- Conflicts with the Boardly rule that Domain must not depend on Doctrine/Symfony.
- Forces persistence concerns such as column metadata, repository metadata, and optimistic-lock versioning into the domain class.
- Makes Doctrine hydration constraints part of the aggregate design.
- Current `Account` has a private constructor and readonly fields, so direct ORM hydration may force domain changes for persistence rather than business reasons.

Assessment: not recommended.

### B. XML/PHP mapping for Domain Account

Pros:

- Avoids Doctrine attributes in the domain class.
- Keeps mapping metadata outside PHP domain source.
- Can be explicit per context.

Cons:

- Still maps Doctrine directly to the domain aggregate.
- Current `Account` private constructor and readonly fields make hydration/reconstitution fragile.
- Value objects require mapping decisions or custom Doctrine types.
- Optimistic-lock versioning would still need to be represented somewhere compatible with Doctrine and the aggregate lifecycle.
- Persistence constraints can still shape the domain model indirectly.

Assessment: better than attributes, but still couples Doctrine ORM hydration to the aggregate shape.

### C. Separate Doctrine persistence entity in Infrastructure

Pros:

- Keeps Doctrine metadata and ORM lifecycle requirements in Infrastructure.
- Preserves Domain independence from Doctrine, Symfony, and database mapping syntax.
- Allows `Account` to stay behavior-focused while persistence stores scalar fields.
- Makes versioning a persistence concern unless/until the domain needs to expose it.
- Supports explicit mapper tests between persistence representation and domain model.
- Fits ADR-0001 and ADR-0002: infrastructure adapters implement application ports and depend inward.

Cons:

- More classes are required.
- A mapper must convert between scalar persistence fields and value objects.
- The current `Account` has no reconstitution factory, so issue #9 implementation must add an explicit domain-safe reconstitution path or accept an undesirable reflection-based mapper.
- Duplicate field definitions must stay aligned between domain, persistence entity, migration, and tests.

Assessment: recommended.

## 6. Recommended mapping strategy

Use a separate Doctrine persistence representation under `src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/`, with an explicit mapper between the persistence representation and the domain `Account`.

Recommended conceptual components:

```text
src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/
├── DoctrineAccount.php
├── DoctrineAccountRepository.php
└── AccountMapper.php
```

The exact class names can change during implementation, but the responsibilities should stay separated:

- persistence entity: scalar Doctrine-mapped representation of `accounts`;
- mapper: converts between scalar persistence data and domain value objects/aggregate;
- repository adapter: implements `AccountRepositoryInterface`.

The current `Account` aggregate cannot be cleanly rehydrated without either:

- adding an explicit domain reconstitution factory in a future implementation step, or
- using reflection/private-constructor workarounds in infrastructure.

The recommended implementation should add a deliberate reconstitution path to `Account` before adding the mapper. That is a domain change, so it must be done in the implementation issue, not in this planning task.

## 7. Required database table and constraints

Required table:

```text
accounts
- id uuid primary key
- email varchar unique not null
- password_hash varchar not null
- name varchar(100) not null
- status varchar not null
- is_system_admin boolean not null default false
- created_at timestamp not null
- updated_at timestamp not null
- approved_at timestamp nullable
- rejected_at timestamp nullable
- disabled_at timestamp nullable
- version int not null
```

Required constraints and indexes:

- primary key on `id`;
- unique index/constraint on `email`;
- `name` length capped at 100 to match `AccountName`;
- non-null constraints for identity, credential hash, lifecycle state, system-admin flag, creation/update timestamps, and version;
- default `false` for `is_system_admin`;
- valid status values must be enforced by application/domain hydration at minimum; a database check constraint can be considered if it is portable for the selected DB platform;
- optimistic locking must use `version`.

Explicit exclusions:

- no project membership columns;
- no project/workflow/issue permission columns;
- no refresh token columns;
- no access token/JWT/session/frontend state columns;
- no plain password column.

## 8. Repository adapter behavior

The Doctrine repository adapter should implement the application port only. Application handlers should not depend on Doctrine repositories directly.

Required behavior:

- `save(Account $account): void`
  - insert new accounts and update existing accounts;
  - persist normalized email from `Email::value()`;
  - persist only `PasswordHash::value()`, never plain passwords;
  - preserve lifecycle timestamps accurately;
  - participate in the surrounding application transaction when one exists;
  - rely on Doctrine/DB optimistic locking for stale concurrent writes.

- `get(AccountId $id): Account`
  - load by id;
  - return rehydrated domain `Account`;
  - throw a missing-account exception when not found.

- `find(AccountId $id): ?Account`
  - load by id;
  - return `null` when not found.

- `findByEmail(Email $email): ?Account`
  - look up by normalized email value;
  - return `null` when not found.

- `existsByEmail(Email $email): bool`
  - efficient existence check by normalized email;
  - used by registration/application flow but not a replacement for the DB unique constraint.

- `countActiveSystemAdmins(): int`
  - count only rows where `is_system_admin = true` and `status = active`;
  - support last-active-system-admin safety rules.

## 9. Migration requirements

Create one Doctrine migration for the initial `accounts` table.

Migration requirements:

- create `accounts` with the columns listed above;
- add primary key on `id`;
- add unique index on `email`;
- use a DB-compatible UUID column type for the configured platform;
- use immutable timestamp-compatible column types consistently;
- define `version int not null`;
- avoid adding unrelated IdentityAccess tables such as refresh sessions in issue #9.

The migration must be reviewed against the generated SQL before merging.

## 10. Testing strategy

Existing tests cover the domain model and value objects. Issue #9 should add infrastructure/persistence tests.

Recommended tests:

- schema/migration test or migration SQL review proving the `accounts` table shape, unique email constraint, and required columns;
- repository test for saving and reloading a registered account;
- repository test for saving and reloading a system-admin account;
- repository test for approved, rejected, and disabled lifecycle timestamp persistence;
- `find()` returns `null` for missing id;
- `get()` throws the expected missing-account exception;
- `findByEmail()` uses normalized email values;
- `existsByEmail()` returns accurate results;
- `countActiveSystemAdmins()` counts only active system admins;
- duplicate normalized email fails at database level;
- optimistic-lock/concurrent update behavior prevents stale writes;
- invalid persisted status cannot silently become a valid domain account;
- password hash is persisted as hash only and no raw password/token/session fields exist.

Suggested test paths:

```text
tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/AccountRepositoryTest.php
tests/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/AccountMappingTest.php
```

## 11. Risks and open questions

Risks:

- The current `Account` aggregate has no explicit reconstitution factory, so persistence implementation could be tempted to use reflection or Doctrine direct hydration.
- The current `AccountRepositoryInterface` does not match the expected issue #9 API.
- Current Doctrine config auto-maps attributes across all `src`, which is not ideal for infrastructure-only XML/PHP mapping or a narrowly mapped persistence entity.
- Optimistic locking needs careful placement because `version` is required in storage but is not currently exposed by the domain model.
- Email uniqueness must be enforced by the database, not only application checks.
- `get()` requires a clear missing-account exception type.

Open questions:

- Should `version` remain purely on the persistence entity, or should the domain expose it for explicit optimistic-lock semantics?
- Should the Doctrine persistence representation use attributes inside Infrastructure, XML mapping, or PHP mapping?
- Should Doctrine auto-mapping over all `src` be narrowed before adding real persistence entities?
- Which DB platform-specific UUID/timestamp column types should the migration use after checking the configured database target?

## 12. Explicit non-goals

This issue #9 planning does not include:

- implementing a Doctrine repository;
- creating a migration;
- modifying `Account`;
- modifying `AccountRepositoryInterface`;
- adding Doctrine entities or mappings;
- adding tests;
- changing Doctrine configuration;
- changing composer dependencies;
- implementing `RefreshSession` persistence;
- implementing authentication handlers, controllers, JWT, cookies, or password hashing;
- implementing project/workflow/issue permissions;
- introducing Redis, RabbitMQ, or OpenSearch;
- storing refresh tokens, access tokens, JWTs, or frontend session state in `accounts`.
