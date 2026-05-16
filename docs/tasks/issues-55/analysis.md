# Analysis: Phase 5 - Start core Product domain with Projects MVP

## Inputs reviewed
- GitHub Issue #55: Phase 5: Start core product domain with Projects MVP
- ADR-0008: Use Layered Project Access Control with Future Database-Level Protection
- ADR-0009: Use PostgreSQL Schemas for Bounded Context and Infrastructure Boundaries
- ADR-0010: Store Project Icons as Controlled Icon Keys
- Repository structure and current state of `src/Boardly/Projects`

## Current state
- The Projects bounded context directory `src/Boardly/Projects` is initialized with standard hexagonal layers (Domain, Application, Infrastructure, Interfaces) but contains no files.
- Persistence for IdentityAccess is already implemented in the `public` schema (`accounts`, `refresh_sessions`).
- No project-related database tables or migrations exist yet.

## Architecture impact
- **Bounded Context**: Establishes `Projects` as the first product-domain context.
- **Persistence Boundary**: Introduces the first dedicated PostgreSQL schema `projects` (ADR-0009).
- **Access Control Layering**: Implements owner-based access as the first layer of a multi-phase authorization strategy (ADR-0008).
- **Decoupling**: Enforces strict decoupling between `IdentityAccess` and `Projects` by using scalar IDs (`owner_account_id`) instead of ORM associations.

## Domain/Application/Infrastructure/UI impact

### Domain
- **Aggregate**: `Project` will be the primary aggregate.
- **Invariants**:
    - Name: Required, non-blank, max 120 chars.
    - IconKey: Required, defaults to `folder`, format `^[a-z][a-z0-9_-]{0,63}$`.
    - Status: `active` or `archived`.
- **Logic**: Handle status transitions (Archive).

### Application
- **Use Cases**:
    - `CreateProject`: Validates input, assigns owner, persists.
    - `ListProjects`: Filters by owner and active status.
    - `GetProject`: Filters by owner and ID.
    - `ArchiveProject`: Checks ownership, updates status.

### Infrastructure
- **Persistence**: Doctrine mapping to `projects.projects`.
- **Repositories**: Must implement access-aware methods (e.g., `findAccessibleById`, `findAllAccessible`).
- **Migrations**: Create `projects` schema and `projects.projects` table with proper constraints and FK to `public.accounts`.

### Interfaces (API)
- **Endpoints**:
    - `POST /api/projects`
    - `GET /api/projects`
    - `GET /api/projects/{id}`
    - `POST /api/projects/{id}/archive`
- **Contracts**: Use camelCase (e.g., `iconKey`). Return 404 for unauthorized access to prevent ID enumeration/probing.

## Frontend impact
- **Routing**: `/app/projects` for listing and `/app/projects/{id}` for details.
- **State**: Project list and creation modal.
- **Components**: Project card/list item showing name, icon (mapped from `iconKey`), and status.
- **Architecture**: Should follow ADR-0006 (Context-based frontend hexagonal architecture).

## Security / permissions / audit impact
- **Authorization**: Owner-only for MVP.
- **Information Leakage**: return `404` instead of `403` for projects not owned by the user.
- **Referential Integrity**: PostgreSQL FK ensures `owner_account_id` exists in `public.accounts`.

## Async / search / cache impact
- **Async**: Not strictly required for MVP, but project creation/archival may emit domain events for future search indexing or notifications.
- **Search**: Future indexing of projects in OpenSearch/Elasticsearch will be required.
- **Cache**: No immediate cache requirements, but project lists are good candidates for future caching.

## Risks and mitigations
- **Risk**: Coupling with `IdentityAccess` via Doctrine.
    - **Mitigation**: Use scalar IDs and value objects for account references as per ADR-0009.
- **Risk**: Missing access checks in repositories.
    - **Mitigation**: Use explicit `findAccessible...` repository methods and avoid generic `find` in application handlers (ADR-0008).
- **Risk**: Inconsistent icon mapping.
    - **Mitigation**: Use controlled `icon_key` strings and a central frontend icon registry (ADR-0010).

## Test strategy
- **Domain Tests**: Unit tests for `Project` aggregate invariants (name, icon format, status transitions).
- **Application Tests**: Use case tests with mocked repositories to verify ownership filtering and command/query logic.
- **Infrastructure Tests**: Use `php bin/console doctrine:schema:validate --skip-sync` for mapping validation, then rely on integration tests and migration checks for the `projects` schema, `projects.projects` table, `owner_account_id` FK, `icon_key` constraint, and repository access filtering.
- **API Tests**: Functional tests (Symfony WebTestCase) for end-to-end flows, including unauthorized access attempts.

## Implementation notes
- Ensure `doctrine.yaml` is updated to include the new `BoardlyProjects` mapping.
- Migration must create the schema `CREATE SCHEMA IF NOT EXISTS projects`.
- The `ProjectEntity` must use `#[ORM\Table(name: 'projects', schema: 'projects')]`.

## Refined checklist
- [ ] Initialize `Projects` domain model with `Project` aggregate and `ProjectIconKey` value object.
- [ ] Create `CreateProject`, `ListProjects`, `GetProject`, `ArchiveProject` use cases.
- [ ] Configure Doctrine mapping for `Boardly/Projects`.
- [ ] Implement `ProjectRepository` with access-aware methods.
- [ ] Create DB migration for `projects` schema and `projects.projects` table.
- [ ] Implement API Controllers and DTOs.
- [ ] Update OpenAPI documentation.
- [ ] Add comprehensive test suite (Domain, Application, Infrastructure, API).

## Next artifact
Expected next artifact: `docs/tasks/issues-55/planning.md`
