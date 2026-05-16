# Implementation Checklist: GitHub issue #55: Start core Product domain with Projects MVP

## Phase 1: Shared Kernel & Domain
- [x] Create `ProjectId` in `src/Boardly/SharedKernel/Domain/ValueObject`.
- [x] Create `ProjectStatus`, `ProjectName`, `ProjectIconKey` in `src/Boardly/Projects/Domain/ValueObject`.
- [x] Create `Project` aggregate in `src/Boardly/Projects/Domain/Model`.
- [x] Create domain results and events: `ProjectCreated`, `ProjectArchived`.
- [x] Unit tests for `Project` aggregate and value objects.

## Phase 2: Application Layer
- [x] Create `ProjectRepositoryInterface` in `src/Boardly/Projects/Application/Port`.
- [x] Implement `CreateProject` use case (Command, Handler, Result).
- [x] Implement `ListProjects` use case (Query/Command, Handler, Result).
- [x] Implement `GetProject` use case (Query/Command, Handler, Result).
- [x] Implement `ArchiveProject` use case (Command, Handler, Result).
- [x] Use case unit tests with mocked repositories.

## Phase 3: Infrastructure (Persistence)
- [x] Create `ProjectEntity` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity`.
- [x] Create `ProjectRepository` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository`.
- [x] Update `config/packages/doctrine.yaml` with `BoardlyProjects` mapping.
- [x] Create DB migration for `projects` schema and `projects.projects` table.
- [x] Integration tests for repository and Doctrine mapping.

## Phase 4: Interfaces (API)
- [x] Create `ProjectController` in `src/Boardly/Projects/Interfaces/Http/Controller`.
- [x] Create Request DTOs and Response DTOs.
- [x] Configure routing if necessary (or use attributes).
- [x] Update OpenAPI documentation (likely in `Interfaces/Http/OpenApi`).
- [x] Functional tests for API endpoints (happy path + unauthorized access).

## Phase 5: Final Validation
- [x] `php bin/console doctrine:schema:validate --skip-sync` returns no mapping errors.
- [x] Explicit migration/integration DB checks confirm the `projects` schema, `projects.projects` table, owner FK, `icon_key` constraint, and access-aware repository behavior.
- [x] `php bin/phpunit` passes for all new tests.
- [ ] API functional tests confirm that accounts can only see/modify their own projects (404 for others).
- [ ] Migration can be run up and down safely.
