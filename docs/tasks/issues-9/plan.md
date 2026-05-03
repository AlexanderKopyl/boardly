# Issue #9 Plan: Account Persistence

## 1. Align AccountRepositoryInterface if needed

1. Update `src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php` to match the issue #9 contract:

   ```php
   public function save(Account $account): void;
   public function get(AccountId $id): Account;
   public function find(AccountId $id): ?Account;
   public function findByEmail(Email $email): ?Account;
   public function existsByEmail(Email $email): bool;
   public function countActiveSystemAdmins(): int;
   ```

2. Replace current `findById()` usage with `find()` if any usage exists by implementation time.
3. Add or reuse an `AccountNotFound` exception for `get()`.
4. Decide before coding whether `version` remains persistence-only or becomes visible on the domain aggregate.
5. Add an explicit domain reconstitution path if the mapper needs to rebuild `Account` from persisted state.

Exit criteria:

- Application port matches issue #9 expected API.
- Missing-account semantics are explicit.
- No Doctrine dependency is introduced into Domain or Application.

## 2. Add persistence entity or mapping structure

1. Create an Infrastructure persistence representation under:

   ```text
   src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/
   ```

2. Prefer a scalar Doctrine persistence entity such as `DoctrineAccount` or `AccountRecord`.
3. Include fields matching the `accounts` table:

   ```text
   id
   email
   passwordHash
   name
   status
   isSystemAdmin
   createdAt
   updatedAt
   approvedAt
   rejectedAt
   disabledAt
   version
   ```

4. Keep Doctrine attributes/XML/PHP mapping in Infrastructure only.
5. Do not map the domain `Account` directly unless a later ADR/design change explicitly accepts that coupling.

Exit criteria:

- Persistence representation contains only storage concerns.
- Domain `Account` remains free of Doctrine/Symfony metadata.

## 3. Add mapper between domain Account and persistence representation

1. Add an `AccountMapper` in the same Infrastructure persistence namespace.
2. Implement domain-to-persistence conversion:

   - `AccountId::value()` to `id`;
   - `Email::value()` to `email`;
   - `PasswordHash::value()` to `password_hash`;
   - `AccountName::value()` to `name`;
   - `AccountStatus::value()` to `status`;
   - lifecycle timestamps copied directly.

3. Implement persistence-to-domain conversion:

   - validate `id` through `AccountId::fromString()`;
   - validate `email` through `Email::fromString()`;
   - validate `password_hash` through `PasswordHash::fromString()`;
   - validate `name` through `AccountName::fromString()`;
   - validate `status` through `AccountStatus::fromString()`;
   - reconstitute `Account` without producing lifecycle events.

4. Avoid reflection-based reconstitution unless explicitly accepted as temporary technical debt.

Exit criteria:

- Mapping is explicit and unit/integration-testable.
- Invalid persisted scalar values do not silently create invalid domain objects.
- Rehydration does not emit domain events.

## 4. Add Doctrine repository adapter

1. Add `DoctrineAccountRepository` in Infrastructure.
2. Implement `AccountRepositoryInterface`.
3. For `save()`:

   - find existing persistence row by id;
   - create a new persistence row when missing;
   - update scalar fields from the domain account;
   - let Doctrine manage insert/update and optimistic locking;
   - rely on DB unique constraint for final email uniqueness.

4. For `get()`:

   - delegate to `find()`;
   - throw `AccountNotFound` when missing.

5. For `find()` and `findByEmail()`:

   - load persistence row;
   - map to domain account;
   - return `null` when missing.

6. For `existsByEmail()`:

   - use an efficient scalar count/existence query.

7. For `countActiveSystemAdmins()`:

   - count rows with `status = active` and `is_system_admin = true`.

Exit criteria:

- Adapter is the only Doctrine-aware implementation of the account repository port.
- Application code can depend only on `AccountRepositoryInterface`.

## 5. Add Doctrine mapping config if needed

1. Decide the exact mapping mechanism for the Infrastructure persistence entity:

   - attributes on the Infrastructure persistence entity are acceptable because Infrastructure may depend on Doctrine;
   - XML/PHP mapping is also acceptable if the project chooses explicit mapping files;
   - attributes on Domain `Account` are not recommended.

2. If using XML/PHP mapping, add a dedicated mapping entry for the IdentityAccess Infrastructure persistence namespace.
3. Review whether current broad `App` attribute auto-mapping over all `src` should be narrowed to avoid scanning domain objects as persistence candidates.
4. Keep mapping config context-specific and avoid global `src/Entity` style structure.

Exit criteria:

- Doctrine can discover the persistence entity/mapping.
- Domain classes are not treated as Doctrine entities.

## 6. Add migration for accounts table

1. Generate or write a Doctrine migration under `migrations/`.
2. Create the `accounts` table:

   ```text
   id uuid primary key
   email varchar unique not null
   password_hash varchar not null
   name varchar(100) not null
   status varchar not null
   is_system_admin boolean not null default false
   created_at timestamp not null
   updated_at timestamp not null
   approved_at timestamp nullable
   rejected_at timestamp nullable
   disabled_at timestamp nullable
   version int not null
   ```

3. Add a unique index/constraint on `email`.
4. Consider a status check constraint only if it works cleanly with the selected DB platform.
5. Do not add refresh-session tables in issue #9.

Exit criteria:

- Migration creates only the `accounts` persistence needed for issue #9.
- Generated SQL is reviewed for the target DB platform.

## 7. Add persistence/integration tests

1. Add repository integration tests for:

   - save and reload registered account;
   - save and reload system-admin account;
   - lifecycle timestamp persistence for approved/rejected/disabled accounts;
   - `find()` missing account returns `null`;
   - `get()` missing account throws;
   - `findByEmail()` with normalized email;
   - `existsByEmail()` true/false cases;
   - `countActiveSystemAdmins()` excludes non-active and non-admin accounts.

2. Add persistence/mapping tests for:

   - invalid persisted status fails during mapping;
   - duplicate normalized email fails at DB level;
   - password hash is stored as a hash only;
   - no token/session/project permission fields exist in account persistence.

3. Add optimistic-locking test:

   - load the same account twice;
   - save one mutation;
   - attempt to save stale mutation;
   - assert stale write does not overwrite current state.

Exit criteria:

- Repository behavior is covered through the real Doctrine adapter.
- Security-sensitive persistence constraints are tested.
- Concurrency behavior is tested or explicitly documented if deferred.

## 8. Run validation commands

Run focused checks first:

```bash
php -l src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
php -l src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/DoctrineAccountRepository.php
php -l src/Boardly/IdentityAccess/Infrastructure/Persistence/Doctrine/AccountMapper.php
```

Run Doctrine validation commands:

```bash
php bin/console doctrine:schema:validate --env=test
php bin/console doctrine:migrations:diff --env=test
php bin/console doctrine:migrations:migrate --env=test
```

Run test and static analysis commands:

```bash
composer test
composer phpstan
composer qa
```

Adjust exact command prefixes to the local PHP/composer paths used by this repository environment.

Exit criteria:

- PHP syntax checks pass.
- Doctrine schema/mapping validation passes.
- Persistence tests pass.
- Existing domain tests still pass.
- Static analysis passes.

## 9. Final cleanup/checklist

Before closing issue #9 implementation:

- `AccountRepositoryInterface` matches the expected API.
- Doctrine-aware code lives in Infrastructure.
- Domain `Account` has no Doctrine/Symfony attributes or imports.
- `accounts` table matches the documented shape.
- `email` has a database unique constraint.
- `version` is present and optimistic locking behavior is covered.
- `countActiveSystemAdmins()` counts only active system admins.
- No raw password, refresh token, access token, JWT, frontend session state, project membership, or project permission data is stored in `accounts`.
- Migration is limited to Account persistence.
- Tests cover mapper, repository, uniqueness, lifecycle timestamps, and concurrency.
- No unrelated refactors or future IdentityAccess scope are included.
