# Planning: GitHub issue #55: Start core Product domain with Projects MVP

## Goal
Implement a vertical slice for Projects MVP within a new "Projects" bounded context. This includes creating, listing, retrieving, and archiving projects, following DDD and Hexagonal Architecture.

## Scope
- Backend implementation of Projects MVP.
- Domain model: `Project` aggregate.
- Application layer: Use cases for Create, List, Get, Archive.
- Infrastructure: Doctrine persistence in a dedicated `projects` PostgreSQL schema.
- API: REST endpoints for the use cases.
- Access Control: Owner-based access (MVP layer).

## Non-goals
- Frontend implementation (separate phase).
- Tasks, Boards, memberships, RBAC, search indexing, notifications, uploads.
- Project-level permissions beyond simple ownership.

## Relevant context
- ADR-0008: Owner-based access for MVP.
- ADR-0009: Dedicated `projects` schema in PostgreSQL.
- ADR-0010: Icons as controlled keys.
- Existing patterns in `IdentityAccess` (static factory methods, value objects, transactional outbox).

## Subagents to use
- `codebase_investigator`: For deep dives into existing patterns if needed.
- `generalist`: For batch file creation and refactoring.

## Skills to use
- `task-implementation`: For step-by-step execution.
- `testing-strategy`: For ensuring comprehensive coverage.

## Files and directories likely to change
- `src/Boardly/SharedKernel/Domain/ValueObject/ProjectId.php` (new)
- `src/Boardly/Projects/Domain/` (new domain models and value objects)
- `src/Boardly/Projects/Application/` (new use cases)
- `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/` (new entities and repositories)
- `src/Boardly/Projects/Interfaces/Http/` (new controllers and DTOs)
- `config/packages/doctrine.yaml` (modified for mapping)
- `migrations/` (new migration file)

## Implementation checklist

### Phase 1: Shared Kernel & Domain
- [ ] Create `ProjectId` in `src/Boardly/SharedKernel/Domain/ValueObject`.
- [ ] Create `ProjectStatus`, `ProjectName`, `ProjectIconKey` in `src/Boardly/Projects/Domain/ValueObject`.
- [ ] Create `Project` aggregate in `src/Boardly/Projects/Domain/Model`.
- [ ] Create domain results and events: `ProjectCreated`, `ProjectArchived`.
- [ ] Unit tests for `Project` aggregate and value objects.

### Phase 2: Application Layer
- [ ] Create `ProjectRepositoryInterface` in `src/Boardly/Projects/Application/Port`.
- [ ] Implement `CreateProject` use case (Command, Handler, Result).
- [ ] Implement `ListProjects` use case (Query/Command, Handler, Result).
- [ ] Implement `GetProject` use case (Query/Command, Handler, Result).
- [ ] Implement `ArchiveProject` use case (Command, Handler, Result).
- [ ] Use case unit tests with mocked repositories.

### Phase 3: Infrastructure (Persistence)
- [ ] Create `ProjectEntity` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity`.
- [ ] Create `ProjectRepository` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository`.
- [ ] Update `config/packages/doctrine.yaml` with `BoardlyProjects` mapping.
- [ ] Create DB migration for `projects` schema and `projects.projects` table.
- [ ] Integration tests for repository and Doctrine mapping.

### Phase 4: Interfaces (API)
- [ ] Create `ProjectController` in `src/Boardly/Projects/Interfaces/Http/Controller`.
- [ ] Create Request DTOs and Response DTOs.
- [ ] Configure routing if necessary (or use attributes).
- [ ] Update OpenAPI documentation (likely in `Interfaces/Http/OpenApi`).
- [ ] Functional tests for API endpoints (happy path + unauthorized access).

## Validation checklist
- [ ] `php bin/console doctrine:schema:validate` returns no errors.
- [ ] `php bin/phpunit` passes for all new tests.
- [ ] API functional tests confirm that accounts can only see/modify their own projects (404 for others).
- [ ] Migration can be run up and down safely.

## Rollback
- Revert Git changes.
- Roll back the database migration: `php bin/console doctrine:migrations:execute --down <Version>`.
- Remove the `projects` schema manually if necessary (though migration should handle it).

## Risks
- **Coupling**: Risk of coupling `Projects` to `IdentityAccess` Doctrine entities. *Mitigation*: Use scalar `owner_account_id` as per ADR-0009.
- **Security**: Inaccessible projects might leak existence. *Mitigation*: Return 404 instead of 403 for unauthorized project access as per ADR-0008.
- **Schema Management**: Missing schema creation in migration. *Mitigation*: Explicit `CREATE SCHEMA IF NOT EXISTS projects` in migration.

## Open questions
- None at this stage.

## Next artifact
Expected next artifact: `docs/tasks/issues-55/analysis.md` (Already exists, so implementation is next).
