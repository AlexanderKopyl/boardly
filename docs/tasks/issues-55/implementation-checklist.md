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
- [ ] Create `ProjectEntity` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity`.
- [ ] Create `ProjectRepository` in `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository`.
- [ ] Update `config/packages/doctrine.yaml` with `BoardlyProjects` mapping.
- [ ] Create DB migration for `projects` schema and `projects.projects` table.
- [ ] Integration tests for repository and Doctrine mapping.

## Phase 4: Interfaces (API)
- [ ] Create `ProjectController` in `src/Boardly/Projects/Interfaces/Http/Controller`.
- [ ] Create Request DTOs and Response DTOs.
- [ ] Configure routing if necessary (or use attributes).
- [ ] Update OpenAPI documentation (likely in `Interfaces/Http/OpenApi`).
- [ ] Functional tests for API endpoints (happy path + unauthorized access).

## Phase 5: Final Validation
- [ ] `php bin/console doctrine:schema:validate` returns no errors.
- [ ] `php bin/phpunit` passes for all new tests.
- [ ] API functional tests confirm that accounts can only see/modify their own projects (404 for others).
- [ ] Migration can be run up and down safely.
