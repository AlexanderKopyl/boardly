# ADR-0008: Use Layered Project Access Control with Future Database-Level Protection

## Status

Accepted

## Date

2026-05-14

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / database architecture owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

The first product-domain milestone introduces the Projects bounded context with a minimal vertical slice:

```text
POST /api/projects
GET /api/projects
GET /api/projects/{projectId}
POST /api/projects/{projectId}/archive
```

The MVP authorization rule is intentionally simple:

```text
An authenticated account can access projects where owner_account_id = current account id.
```

However, Boardly will later need richer project-level authorization:

```text
- project memberships;
- project roles;
- project permissions;
- task-level actions;
- board/workflow operations;
- invitation flows;
- administrative actions;
- database-level defense-in-depth.
```

The access model must be designed so the MVP stays small, but the database schema can evolve toward production-grade authorization without rewriting the core Projects persistence model.

Without an explicit decision, the project risks:

```text
- storing project permissions in accounts;
- coupling global account identity with project-specific access;
- implementing access only in controllers;
- creating premature generic RBAC tables without real use cases;
- adding indexes without known access patterns;
- relying only on application checks for durable access boundaries;
- introducing PostgreSQL Row-Level Security too early and making local development harder.
```

## Decision

We will use layered project access control.

The access-control evolution path is:

```text
Phase 1: owner-based project access
Phase 2: project memberships with project-level roles
Phase 3: role-to-permission mapping when actions justify it
Phase 4: PostgreSQL Row-Level Security for database-level defense-in-depth
Phase 5: audit/access decision history if required by product or security needs
```

For the Projects MVP, access is owner-based:

```text
projects.owner_account_id = current authenticated account id
```

Future project collaboration must use a separate project membership model, not columns on `accounts`.

Database-level protection may be added later with PostgreSQL Row-Level Security, but RLS must be treated as defense-in-depth, not as a replacement for application-layer authorization.

Symfony voters, application services, or dedicated authorization policies remain responsible for business authorization decisions.

PostgreSQL remains responsible for durable integrity, referential consistency, uniqueness, access-boundary support, and optional database-level access enforcement.

## Scope

This decision applies to:

- Projects bounded context;
- future Tasks / Issues bounded context where access is project-scoped;
- project membership persistence;
- project role and permission persistence;
- Symfony application authorization for project-scoped actions;
- PostgreSQL schema, constraints, indexes, and future RLS policies;
- Doctrine repository query design for access-controlled reads and writes.

This decision does not apply to:

- global system-admin capability in IdentityAccess;
- authentication token storage;
- refresh session persistence;
- frontend session state;
- organization-level authorization if a separate organization model is introduced later;
- final enterprise permission matrix design before real product actions exist.

## Architectural Rules

- Project access must be modeled as project-scoped data, not as global account data.
- `accounts` must not store project permissions, project roles, project memberships, or frontend access state.
- The Projects MVP must use `projects.owner_account_id` for owner-based access.
- Project membership must be introduced through a separate table when collaboration is needed.
- Owner access may later be represented both by `projects.owner_account_id` and an owner membership row during migration, but the transition must be explicit.
- Role-to-permission mapping must not be introduced until real actions require permission-level control.
- Application authorization must remain explicit in Symfony application/domain policy code.
- Controllers must not own authorization rules directly.
- Doctrine repositories must not return inaccessible project data and rely on callers to filter it later.
- PostgreSQL constraints must protect durable invariants.
- PostgreSQL indexes must be tied to real access patterns.
- PostgreSQL Row-Level Security may be introduced later as defense-in-depth.
- RLS must not become the only place where business authorization rules are defined.
- RLS policies must be documented and tested if enabled.
- Any request-scoped database setting used by RLS must be set transaction-locally, not globally.

## Initial Projects Schema Direction

The first Projects table should support owner-based access and future evolution:

```sql
CREATE TABLE projects (
    id UUID PRIMARY KEY,

    owner_account_id UUID NOT NULL,

    name VARCHAR(120) NOT NULL,
    status VARCHAR(32) NOT NULL,

    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    archived_at TIMESTAMPTZ NULL,

    version INT NOT NULL DEFAULT 1,

    CONSTRAINT projects_status_check
        CHECK (status IN ('active', 'archived')),

    CONSTRAINT projects_name_not_blank_check
        CHECK (length(trim(name)) > 0),

    CONSTRAINT projects_version_positive_check
        CHECK (version > 0)
);
```

Recommended MVP indexes:

```sql
CREATE INDEX projects_owner_status_idx
    ON projects (owner_account_id, status);

CREATE INDEX projects_owner_created_at_idx
    ON projects (owner_account_id, created_at DESC);
```

The main MVP access query is:

```sql
SELECT *
FROM projects
WHERE owner_account_id = :accountId
  AND status = 'active'
ORDER BY created_at DESC;
```

The first index supports default active-project lists.

The second index supports deterministic project listing ordered by creation time for a single account.

Do not add speculative indexes for future screens until those queries exist.

## Future Project Membership Schema Direction

When project collaboration is introduced, add project memberships as separate persistence:

```sql
CREATE TABLE project_memberships (
    id UUID PRIMARY KEY,

    project_id UUID NOT NULL,
    account_id UUID NOT NULL,

    role VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL,

    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,

    version INT NOT NULL DEFAULT 1,

    CONSTRAINT project_memberships_project_account_unique
        UNIQUE (project_id, account_id),

    CONSTRAINT project_memberships_role_check
        CHECK (role IN ('owner', 'admin', 'member', 'viewer')),

    CONSTRAINT project_memberships_status_check
        CHECK (status IN ('active', 'disabled')),

    CONSTRAINT project_memberships_version_positive_check
        CHECK (version > 0)
);
```

Recommended future indexes:

```sql
CREATE INDEX project_memberships_account_status_idx
    ON project_memberships (account_id, status);

CREATE INDEX project_memberships_project_status_idx
    ON project_memberships (project_id, status);

CREATE INDEX project_memberships_account_project_status_idx
    ON project_memberships (account_id, project_id, status);
```

The table exists to answer these access questions efficiently:

```text
- Which projects can this account access?
- Which accounts are active members of this project?
- Does this account have an active membership in this project?
```

## Future Permission Mapping Direction

Do not introduce generic permission tables in the MVP.

When actions justify permission-level control, use an explicit role-to-permission mapping:

```sql
CREATE TABLE project_role_permissions (
    role VARCHAR(32) NOT NULL,
    permission VARCHAR(64) NOT NULL,

    PRIMARY KEY (role, permission),

    CONSTRAINT project_role_permissions_role_check
        CHECK (role IN ('owner', 'admin', 'member', 'viewer')),

    CONSTRAINT project_role_permissions_permission_check
        CHECK (permission IN (
            'project.view',
            'project.update',
            'project.archive',
            'member.invite',
            'member.remove',
            'task.create',
            'task.update',
            'task.delete'
        ))
);
```

This gives Boardly controlled flexibility without making permissions unbounded strings spread across the codebase.

The permission list must evolve through migrations and reviewed application authorization changes.

## Future PostgreSQL Row-Level Security Direction

PostgreSQL Row-Level Security may be used later for database-level protection on project-scoped tables such as:

```text
projects
tasks
comments
attachments
```

A future RLS implementation may use a transaction-local setting:

```sql
SET LOCAL app.current_account_id = '00000000-0000-0000-0000-000000000000';
```

An example owner-based policy:

```sql
ALTER TABLE projects ENABLE ROW LEVEL SECURITY;

CREATE POLICY projects_select_policy
ON projects
FOR SELECT
USING (
    owner_account_id = current_setting('app.current_account_id')::uuid
);
```

A future membership-based policy may check `project_memberships` instead of direct ownership.

RLS adoption requires a separate migration plan and tests because it changes database behavior globally for affected tables.

## Alternatives Considered

### Option A: Application-only authorization

Pros:

- simplest MVP implementation;
- easy to understand in Symfony;
- no database session-state complexity;
- fast local development.

Cons:

- repository bugs can accidentally return inaccessible rows;
- raw SQL/read-model queries must remember access filters;
- weaker defense-in-depth;
- harder to protect future reporting or admin queries.

Partially accepted for MVP only. Application authorization remains required, but database-supported access boundaries will be added as the product matures.

### Option B: Immediate generic RBAC schema

Examples:

```text
roles
permissions
role_permissions
user_permissions
```

Pros:

- flexible;
- familiar pattern;
- can model many future actions.

Cons:

- premature complexity for the Projects MVP;
- risks generic abstractions before real product actions exist;
- may blur global roles and project-scoped roles;
- can produce weak queries and unnecessary indexes;
- increases implementation surface before tasks, boards, and workflows exist.

Rejected for the MVP.

### Option C: Project memberships with role column

Pros:

- good fit for Jira-like project collaboration;
- keeps access project-scoped;
- simple query model;
- easy to index;
- clear migration path from owner-based MVP;
- avoids polluting accounts.

Cons:

- role semantics must be maintained in application code;
- role changes need careful authorization checks;
- owner migration must be planned if owner is later represented as a membership row.

Accepted as the next access-control step after the MVP.

### Option D: Immediate PostgreSQL Row-Level Security

Pros:

- strong database-level protection;
- reduces impact of missing access filters in SQL;
- useful for defense-in-depth.

Cons:

- adds complexity before access rules stabilize;
- requires reliable request/account context propagation to DB transactions;
- can surprise Doctrine queries and tests;
- may make debugging harder;
- policy mistakes can break production access broadly.

Rejected for the MVP. Accepted as a future defense-in-depth layer after project access patterns stabilize.

## Consequences

### Positive

- The Projects MVP remains small and shippable.
- Future project collaboration has a clear database path.
- Project access stays project-scoped instead of leaking into `accounts`.
- Indexes are tied to concrete access queries.
- PostgreSQL can later enforce additional protection without replacing Symfony authorization.
- The model supports DDD and Hexagonal Architecture boundaries.

### Negative

- A future migration will be needed to introduce `project_memberships`.
- Access checks will initially be duplicated between application policies and repository filters.
- Future RLS adoption will require careful test and migration work.
- Role-to-permission mapping is deferred, so early roles must remain simple.

### Neutral / Operational

- MVP project queries must always filter by `owner_account_id`.
- Future membership queries must join or query `project_memberships`.
- Indexes must be revisited when default project lists, archive filters, pagination, and task queries are implemented.
- RLS must be introduced only after local/test/prod database connection behavior is understood.

## Trade-offs

- We accept owner-based access now in exchange for a smaller first product slice.
- We accept a later membership migration in exchange for avoiding premature RBAC.
- We accept application-layer authorization as the primary rule engine in exchange for keeping business decisions visible in code.
- We accept future RLS complexity only when it gives real defense-in-depth value.
- We avoid using PostgreSQL as the domain layer, while still using it to protect durable consistency and access boundaries.

## Impact on Layers

### Domain

- Project remains the aggregate for project lifecycle behavior.
- Project ownership is part of project creation context.
- Domain rules such as valid status transitions remain in the domain model.
- The domain must not depend on Doctrine, PostgreSQL RLS, or Symfony Security.
- Future membership can become its own aggregate or entity depending on collaboration rules.

### Application

- Use cases must receive the current authenticated account id.
- Project commands and queries must enforce access through explicit policies or authorization services.
- Application handlers must not assume repository methods return globally accessible data.
- Archive/update operations must verify access before mutation.

### Infrastructure

- Doctrine repositories must include access filters in query methods.
- Project persistence must use PostgreSQL constraints for durable invariants.
- Indexes must match real project access patterns.
- Future RLS setup must be implemented in Infrastructure, not Domain.
- Transaction-local account context for RLS must be handled safely if introduced.

### Interfaces / UI

- API responses must not expose internal permission implementation details unless intentionally part of the API contract.
- Missing and inaccessible projects should use the API error strategy without leaking sensitive existence information.
- Frontend must not be the source of authorization truth.

## Impact on DDD

This decision keeps account identity separate from project access.

`Account` remains part of IdentityAccess.

`Project` belongs to the Projects bounded context.

Future `ProjectMembership` belongs to the Projects context unless a separate collaboration/access context is introduced later through an ADR.

Repository ports should expose access-aware methods, for example:

```text
findAccessibleById(ProjectId $projectId, AccountId $accountId)
listAccessibleActiveProjects(AccountId $accountId)
```

Do not expose generic `find($id)` methods to application use cases when access checks are required.

## Impact on EDA

This decision does not require new events for the MVP beyond normal project lifecycle events if introduced.

Future membership changes may produce domain events such as:

```text
ProjectMemberAdded
ProjectMemberRoleChanged
ProjectMemberRemoved
```

If those events need external delivery, they must follow the Transactional Outbox decision.

Business state changes and outbox records must be committed in the same database transaction.

## Impact on CQRS

Project list queries must be access-aware.

Future read models, projections, OpenSearch indexes, or dashboard data must not bypass project access.

If a read model stores project-derived data, it must include enough access context or be filtered through membership/project access at query time.

Search indexing must not expose inaccessible projects, tasks, comments, or attachments.

## Symfony Implementation Direction

Symfony implementation may use:

```text
- Security user/principal to obtain current account id;
- application authorization services or policies;
- Symfony voters for interface-level authorization decisions where useful;
- Doctrine repositories for access-filtered persistence queries;
- Doctrine Migrations for schema changes;
- Messenger/Outbox only for async event delivery, not direct authorization decisions.
```

Symfony mechanisms are implementation details.

The architecture rule is that project access is project-scoped, explicit, database-supported, and not hidden in controllers.

## PostgreSQL / Doctrine Implementation Direction

For MVP:

- create `projects` with `owner_account_id`;
- use `timestamptz` for timestamps;
- use `status` check constraint for known project statuses;
- use `version` for optimistic locking;
- add only access-pattern indexes needed by current queries;
- keep Doctrine persistence in Infrastructure;
- keep API response DTOs separate from Doctrine entities.

For future memberships:

- create `project_memberships` with unique `(project_id, account_id)`;
- index account/project membership access patterns;
- treat inactive memberships explicitly through `status`;
- do not store raw permission arrays on accounts or projects;
- introduce role-to-permission mapping only when needed.

For future RLS:

- introduce through a dedicated migration and ADR/update;
- set request account context transaction-locally;
- test SELECT/INSERT/UPDATE/DELETE behavior;
- ensure Doctrine migrations, fixtures, tests, and console commands handle RLS correctly.

## Risks

- Developers may add project permissions to `accounts` because it looks simpler.
- Controllers may start owning authorization checks.
- Repository methods may return projects without access filters.
- Indexes may be added speculatively.
- Future roles may become unbounded strings with inconsistent semantics.
- RLS may be introduced before the access model is stable.
- RLS may break migrations, fixtures, tests, or background workers if account context is missing.
- Read models or OpenSearch indexes may accidentally expose inaccessible data.

## Mitigations

- Review project access changes against this ADR.
- Keep project access inside Projects persistence and application authorization code.
- Prefer access-aware repository method names.
- Add tests for inaccessible project reads and mutations.
- Require migrations for every new role or permission value.
- Avoid permission tables until real actions require them.
- Document RLS policies before enabling them.
- Add integration tests for RLS if enabled.
- Keep search/read-model access filtering explicit.

## Migration / Adoption Plan

1. Implement the Projects MVP with owner-based access using `projects.owner_account_id`.
2. Add access-aware repository methods for list/get/archive operations.
3. Add tests proving one account cannot list, read, or archive another account's projects.
4. Add only MVP indexes required by owner-based project listing.
5. When collaboration is needed, introduce `project_memberships` through a migration.
6. Migrate existing project owners into owner memberships if the application model requires it.
7. Introduce role-to-permission mapping only after real actions require permission-level checks.
8. Evaluate PostgreSQL RLS after membership rules and access queries are stable.
9. If RLS is accepted, add a focused migration, tests, and operational documentation.

## Open Questions

- Should `projects.owner_account_id` remain forever, or should ownership eventually be represented only through `project_memberships.role = 'owner'`?
- Should project owner transfer be supported?
- Should there be exactly one active owner per project?
- Should project roles be fixed by migrations or configurable by workspace/project administrators later?
- Should RLS be enabled only for production-like environments or also for local/test environments?
- Should archived projects remain visible to members by default or require an explicit filter?

## References

- Related ADRs:
  - `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
  - `docs/adr/0002-use-boardly-context-based-source-structure.md`
  - `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`
  - `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- Related docs:
  - `docs/architecture/project-architecture-rules.md`
  - `docs/design/authentication-api-strategy.md`
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
