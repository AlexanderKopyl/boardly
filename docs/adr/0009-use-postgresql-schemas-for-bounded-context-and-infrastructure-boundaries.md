# ADR-0009: Use PostgreSQL Schemas for Bounded Context and Infrastructure Boundaries

## Status

Accepted

## Date

2026-05-14

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / database architecture owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system built as a Symfony modular monolith using DDD, Hexagonal Architecture, Doctrine ORM/DBAL/Migrations, PostgreSQL, RabbitMQ, Redis, OpenSearch/Elasticsearch, and the Transactional Outbox pattern.

Boardly already has IdentityAccess persistence in the default PostgreSQL schema:

```text
public.accounts
public.refresh_sessions
```

The current IdentityAccess Doctrine mapping and migrations use unqualified table names such as `accounts` and `refresh_sessions`.

The next product phase introduces the Projects bounded context. This is the first real product-domain context after IdentityAccess and it creates the first opportunity to decide how PostgreSQL schemas should be used before many product tables exist.

The project also has a future need for stronger database-level protection:

```text
- project-scoped access control;
- future project memberships;
- future project roles and permissions;
- possible PostgreSQL Row-Level Security;
- separate operational/infrastructure tables such as outbox messages;
- different database privileges for application, migration, worker, and reporting users.
```

Without an explicit schema strategy, Boardly risks turning `public` into a mixed namespace containing identity tables, project tables, task tables, outbox tables, audit tables, and future reporting tables.

At the same time, moving existing IdentityAccess tables to a new schema now would create unnecessary migration risk for an already implemented authentication foundation.

## Decision

We will use PostgreSQL schemas to represent meaningful persistence boundaries when they provide architectural, operational, or security value.

Schemas must represent bounded-context or infrastructure boundaries, not individual entities.

For new product bounded contexts, starting with Projects, tables should be created inside a dedicated schema:

```text
projects.projects
projects.project_memberships
projects.project_role_permissions
```

Existing IdentityAccess tables currently in `public` will not be moved immediately only for naming symmetry.

The initial accepted state is:

```text
public.accounts
public.refresh_sessions

projects.projects
projects.project_memberships
projects.project_role_permissions
```

A future migration to:

```text
identity_access.accounts
identity_access.refresh_sessions
```

may be considered later, but only through a separate reviewed ADR or ADR update and production-safe migration plan.

Cross-schema foreign keys are allowed and should be used when they protect durable referential integrity.

For the Projects MVP, this means:

```text
projects.projects.owner_account_id
  -> public.accounts.id
```

This foreign key must be schema-qualified in SQL.

Doctrine associations must not be used to couple bounded contexts by default. Cross-context references should normally be stored as scalar IDs in Doctrine entities and represented as value objects in the domain/application layer.

## Scope

This decision applies to:

- PostgreSQL schema design;
- Doctrine table mapping;
- Doctrine migrations;
- bounded-context persistence layout;
- infrastructure table placement;
- foreign keys across persistence boundaries;
- future database privileges and RLS strategy;
- naming conventions for schema-qualified database objects.

This decision does not apply to:

- PHP namespaces;
- frontend context structure;
- service container boundaries;
- whether a bounded context must become a microservice;
- immediate migration of existing IdentityAccess tables out of `public`;
- final enterprise multi-tenant schema strategy if a tenant model is introduced later.

## Architectural Rules

- Use PostgreSQL schemas for meaningful persistence boundaries.
- Do not create a schema per entity.
- Do not create schemas only because there are many tables.
- Do not use schemas as a replacement for DDD boundaries in code.
- Do not use schemas as a replacement for Symfony/application authorization.
- New product bounded contexts may own a dedicated schema.
- Infrastructure persistence may own dedicated schemas when useful, for example `outbox` or `audit`.
- Existing tables in `public` must not be moved only for cosmetic consistency.
- Cross-schema foreign keys are allowed when they protect durable integrity.
- Cross-context Doctrine `ManyToOne` / `OneToMany` associations should be avoided by default.
- Cross-context references should normally be scalar IDs in persistence and value objects in domain/application code.
- SQL migrations must schema-qualify cross-schema references.
- Doctrine entities for schema-owned tables must declare the schema explicitly.
- Migrations must create schemas before creating tables inside them.
- Database privileges and future RLS policies should use schemas as coarse-grained boundaries where useful.

## Recommended Schema Layout

Current accepted layout:

```text
public.accounts
public.refresh_sessions

projects.projects
```

Future likely layout:

```text
public.accounts
public.refresh_sessions

projects.projects
projects.project_memberships
projects.project_role_permissions
projects.tasks
projects.task_comments
projects.task_assignees

outbox.outbox_messages
outbox.processed_messages

audit.audit_log
```

Possible later cleanup after a separate migration decision:

```text
identity_access.accounts
identity_access.refresh_sessions

projects.projects
projects.project_memberships
projects.project_role_permissions

outbox.outbox_messages
outbox.processed_messages

audit.audit_log
```

The exact location of future `tasks` tables may be revisited when the Tasks/Issues bounded context is designed. They may live in `projects` if tasks are tightly project-scoped, or in a dedicated `issues` / `tasks` schema if that bounded context becomes large enough to justify a separate persistence boundary.

## Initial Projects Schema Direction

The Projects MVP should create its schema explicitly:

```sql
CREATE SCHEMA IF NOT EXISTS projects;
```

The first project table should live inside that schema:

```sql
CREATE TABLE projects.projects (
    id VARCHAR(36) PRIMARY KEY,
    owner_account_id VARCHAR(36) NOT NULL,

    name VARCHAR(120) NOT NULL,
    status VARCHAR(32) NOT NULL,

    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    archived_at TIMESTAMPTZ NULL,

    version INT NOT NULL DEFAULT 1,

    CONSTRAINT projects_projects_owner_account_fk
        FOREIGN KEY (owner_account_id)
        REFERENCES public.accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT projects_projects_status_check
        CHECK (status IN ('active', 'archived')),

    CONSTRAINT projects_projects_name_not_blank_check
        CHECK (length(trim(name)) > 0),

    CONSTRAINT projects_projects_version_positive_check
        CHECK (version > 0)
);
```

Use `VARCHAR(36)` for `owner_account_id` while `public.accounts.id` is stored as `VARCHAR(36)`.

Do not use `UUID` for `projects.owner_account_id` unless `accounts.id` is migrated to PostgreSQL `uuid` first.

Recommended initial indexes:

```sql
CREATE INDEX idx_projects_projects_owner_status
    ON projects.projects (owner_account_id, status);

CREATE INDEX idx_projects_projects_owner_created_at
    ON projects.projects (owner_account_id, created_at DESC);
```

## Foreign Key Direction

Cross-schema foreign keys are acceptable.

The important boundary is not the schema. The important question is whether the reference is a durable consistency requirement.

For Boardly, a project owner must reference an existing account, so this is valid:

```text
projects.projects.owner_account_id
  REFERENCES public.accounts(id)
```

Use `ON DELETE RESTRICT` for project ownership.

Do not cascade-delete projects when an account is deleted. Projects are durable business data and must not disappear because an identity record was removed.

This differs from refresh sessions, where cascading delete from account to sessions is acceptable because refresh sessions are security/session data, not product business data.

## Doctrine Implementation Direction

A Projects Doctrine entity must specify the schema explicitly:

```php
#[ORM\Entity]
#[ORM\Table(name: 'projects', schema: 'projects')]
final class ProjectEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'owner_account_id', type: 'string', length: 36)]
    private string $ownerAccountId;
}
```

Avoid this by default across bounded contexts:

```php
#[ORM\ManyToOne(targetEntity: AccountEntity::class)]
private AccountEntity $owner;
```

The preferred model is:

```text
Database: foreign key for referential integrity
Doctrine: scalar ownerAccountId
Domain/Application: AccountId value object
```

This protects database consistency without coupling the Projects bounded context to the IdentityAccess Doctrine entity model.

Doctrine configuration must include a dedicated mapping for Projects when the Projects context is introduced, for example:

```yaml
doctrine:
    orm:
        mappings:
            BoardlyProjects:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity'
                prefix: 'App\\Boardly\\Projects\\Infrastructure\\Persistence\\Doctrine\\Entity'
                alias: BoardlyProjects
```

## Privilege and Security Direction

Schemas make future database privileges easier to reason about.

Future roles may include:

```text
boardly_app
boardly_migration
boardly_worker
boardly_readonly
boardly_reporting
```

Example future privilege direction:

```sql
GRANT USAGE ON SCHEMA projects TO boardly_app;
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA projects TO boardly_app;

REVOKE ALL ON SCHEMA projects FROM boardly_readonly;
```

Schemas also help identify which tables may need Row-Level Security later.

For example, project-scoped tables under `projects.*` are likely candidates for future RLS or access-filtering review.

RLS remains defense-in-depth and does not replace Symfony/application authorization.

## Alternatives Considered

### Option A: Keep all tables in `public`

Pros:

- simplest Doctrine setup;
- shortest SQL;
- fewer migration concerns;
- common default for small Symfony applications.

Cons:

- `public` becomes a mixed namespace as the product grows;
- weaker visual separation between bounded contexts;
- harder to reason about database privileges by area;
- harder to identify future RLS-protected tables;
- infrastructure tables and domain tables become mixed.

Rejected as the long-term strategy, but accepted for existing IdentityAccess tables until a safe migration is justified.

### Option B: Move all existing tables to context schemas immediately

Example:

```text
identity_access.accounts
identity_access.refresh_sessions
projects.projects
```

Pros:

- cleanest naming symmetry;
- strong context separation from the start;
- better future privilege boundaries.

Cons:

- touches working authentication persistence;
- requires Doctrine mapping changes;
- requires foreign key and migration changes;
- increases risk before the Projects MVP is implemented;
- mostly cosmetic at the current stage.

Rejected for now.

### Option C: Start schema-per-bounded-context for new product contexts only

Example:

```text
public.accounts
public.refresh_sessions
projects.projects
```

Pros:

- introduces proper boundaries before product-domain tables multiply;
- avoids risky IdentityAccess migration now;
- creates a clean path for Projects, memberships, permissions, tasks, and RLS;
- keeps migration effort proportional to current product value.

Cons:

- database layout is temporarily asymmetric;
- developers must remember that IdentityAccess is still in `public`;
- cross-schema foreign keys must be schema-qualified.

Accepted.

### Option D: Schema per entity or feature

Example:

```text
project.projects
task.tasks
comment.comments
permission.permissions
```

Pros:

- very explicit table grouping;
- looks organized at first glance.

Cons:

- too fragmented;
- weak architectural meaning;
- harder migrations and privileges;
- noisy SQL;
- encourages database structure to drift away from bounded-context design.

Rejected.

## Consequences

### Positive

- New product contexts get clearer persistence boundaries.
- The database layout better matches DDD bounded-context thinking.
- Future DB privileges can be managed by schema.
- Future RLS candidates are easier to identify.
- Infrastructure tables can be separated from product-domain tables.
- Cross-context integrity can still be enforced with foreign keys.
- Doctrine context coupling is reduced by using scalar IDs instead of cross-context associations.

### Negative

- SQL becomes more verbose because table references are schema-qualified.
- Doctrine mappings must declare schemas explicitly.
- Migrations must create schemas before tables.
- Test databases and fixtures must account for schemas.
- The database layout is temporarily asymmetric because IdentityAccess remains in `public`.

### Neutral / Operational

- Schemas do not improve query speed by themselves.
- Schemas do not physically isolate table storage.
- Schemas do not replace indexes, constraints, or foreign keys.
- Schemas do not replace Symfony authorization.
- Schemas are namespace and privilege boundaries, not service boundaries.

## Trade-offs

- We accept temporary asymmetry in exchange for avoiding unnecessary IdentityAccess migration risk.
- We accept slightly more verbose SQL in exchange for clearer persistence boundaries.
- We accept explicit Doctrine schema mapping in exchange for better long-term database organization.
- We accept cross-schema foreign keys in exchange for durable referential integrity.
- We avoid cross-context Doctrine associations in exchange for preserving bounded-context independence.

## Impact on Layers

### Domain

- Domain models do not know PostgreSQL schema names.
- Domain references across contexts should use IDs/value objects, not Doctrine entities.
- Schema layout must not drive aggregate boundaries.

### Application

- Application services should work with account/project IDs and repository ports.
- Application logic must not rely on PostgreSQL schemas for business decisions.
- Authorization remains explicit in application policies, services, or voters.

### Infrastructure

- Doctrine entities must specify schema names for schema-owned tables.
- Migrations must create schemas and schema-qualified tables.
- Cross-schema foreign keys must be explicit.
- Infrastructure owns database layout and mapping concerns.

### Interfaces / UI

- API contracts must not expose database schema names.
- Frontend must not depend on schema layout.

## Impact on DDD

PostgreSQL schemas are allowed to mirror bounded-context persistence boundaries, but they do not define bounded contexts by themselves.

A bounded context is a language/model/ownership boundary. A PostgreSQL schema is a database namespace and possible privilege boundary.

The preferred alignment is:

```text
Bounded context -> one schema when persistence size/security/lifecycle justify it
```

But this is not mandatory for every small context.

Future contributors must not create a new schema just because a PHP namespace exists.

## Impact on EDA

Infrastructure event tables should not be mixed into product schemas unless there is a clear reason.

Transactional Outbox persistence may later use a dedicated schema:

```text
outbox.outbox_messages
outbox.processed_messages
```

Business state changes and outbox records must still follow the accepted Transactional Outbox decision.

Schema placement does not change event consistency rules.

## Impact on CQRS

Read models and projections may use separate schemas if they become operationally distinct.

Potential future examples:

```text
read_models.project_dashboard
search_indexing.index_jobs
```

Do not introduce read-model schemas before real projections exist.

Access-controlled read models must still respect project access and future RLS/security rules.

## Symfony Implementation Direction

Symfony implementation may require:

```text
- explicit Doctrine mapping per bounded context;
- Doctrine migrations that create schemas;
- schema-qualified raw SQL in DBAL repositories;
- careful schema_filter configuration;
- test database setup that supports schemas;
- doctrine:schema:validate checks after mapping changes.
```

Useful commands after implementation:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
```

Symfony remains an implementation detail. The architectural rule is persistence boundary clarity without coupling bounded contexts through Doctrine associations.

## PostgreSQL Implementation Direction

When introducing a new schema:

```sql
CREATE SCHEMA IF NOT EXISTS projects;
```

When creating tables inside it:

```sql
CREATE TABLE projects.projects (...);
```

When referencing another schema:

```sql
REFERENCES public.accounts (id)
```

When adding future privileges:

```sql
GRANT USAGE ON SCHEMA projects TO boardly_app;
```

Avoid relying on `search_path` for application correctness. Prefer explicit schema mapping and schema-qualified SQL in migrations.

## Risks

- Developers may assume one bounded context must always equal one database schema.
- Developers may create schemas too aggressively.
- Existing IdentityAccess in `public` may look inconsistent.
- Cross-schema Doctrine associations may couple bounded contexts.
- Doctrine migrations may generate unexpected diffs if schema mapping is incomplete.
- Fixtures/tests may fail if schemas are missing.
- Future privilege/RLS setup may be attempted before access rules stabilize.

## Mitigations

- Use schemas only for meaningful persistence boundaries.
- Keep existing IdentityAccess in `public` until a separate migration is justified.
- Start Projects in `projects` schema because it is a new product context.
- Use scalar IDs for cross-context Doctrine references.
- Keep foreign keys schema-qualified.
- Add schema creation to migrations explicitly.
- Run `doctrine:schema:validate` after mapping changes.
- Review future schema additions through ADRs or architecture review.

## Migration / Adoption Plan

1. Do not move `public.accounts` or `public.refresh_sessions` immediately.
2. Add `projects` schema in the first Projects persistence migration.
3. Create `projects.projects` with a schema-qualified FK to `public.accounts(id)`.
4. Map `ProjectEntity` with `#[ORM\Table(name: 'projects', schema: 'projects')]`.
5. Add a dedicated Doctrine mapping entry for the Projects context.
6. Use scalar `ownerAccountId` in persistence instead of a cross-context Doctrine association.
7. Add project access indexes inside the `projects` schema.
8. Later, add `projects.project_memberships` and `projects.project_role_permissions` when required by product features.
9. Later, evaluate whether IdentityAccess should move from `public` to `identity_access` through a separate ADR/update.
10. Later, evaluate `outbox` and `audit` schemas when those infrastructure concerns need clearer operational boundaries.

## Open Questions

- Should IdentityAccess eventually move to `identity_access` schema?
- Should Tasks/Issues live under `projects` schema or a dedicated `issues` / `tasks` schema?
- Should outbox tables be moved from `public` to `outbox` schema?
- Should application, migration, worker, and reporting database users get different schema privileges?
- Should RLS be enabled for all project-scoped schemas or only for selected tables?

## References

- Related ADRs:
  - `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
  - `docs/adr/0002-use-boardly-context-based-source-structure.md`
  - `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`
  - `docs/adr/0008-use-layered-project-access-control-with-future-database-protection.md`
- Related docs:
  - `docs/architecture/project-architecture-rules.md`
  - `docs/design/account-domain-model.md`
- Related issues:
  - `#55 Phase 5: Start core product domain with Projects MVP`
- Related PRs: none

## Review Checklist

- [x] Decision is clear and testable.
- [x] Business context is explained.
- [x] Alternatives are documented.
- [x] Trade-offs are explicit.
- [x] Layer impact is described.
- [x] DDD impact is described.
- [x] EDA impact is described if events are involved.
- [x] CQRS impact is described if commands/queries/read models are involved.
- [x] Symfony implementation is treated as implementation detail.
- [x] Risks and mitigations are documented.
