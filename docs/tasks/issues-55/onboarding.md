# Onboarding: Issue #55 review-fix pass for Projects MVP

## Task contract

- Goal: close the remaining review blockers for Projects by making repository access filtering non-leaky and by expanding the create-project response to the stable project shape.
- Non-goals: new product behavior beyond the review blockers, frontend changes, schema redesign, or unrelated refactors.
- Constraints: preserve DDD/Hexagonal boundaries, keep controllers bus-based, avoid reading secrets, and update task artifacts for completed work.
- Done criteria: repository-level accessibility filtering is used by list/get/archive/delete, create returns the stable project payload, focused tests pass, and checklist/implementation notes are updated.

## Durable guidance loaded

- `AGENTS.md`
- `.codex/config.toml`
- `docs/adr/0008-use-layered-project-access-control-with-future-database-protection.md`
- `docs/adr/0009-use-postgresql-schemas-for-bounded-context-and-infrastructure-boundaries.md`
- `docs/adr/0010-store-project-icons-as-controlled-icon-keys.md`
- Skills: `repo-onboarding`, `task-implementation`

## Candidate files

| File | Reason | Confidence |
| --- | --- | --- |
| `docs/tasks/issues-55/review-fix-checklist.md` | Live checklist for the remaining review blockers and verification plan. | High |
| `src/Boardly/Projects/Application/Port/ProjectRepositoryInterface.php` | Current repository port still exposes generic `find`/`findByOwner` methods that leak filtering to callers. | High |
| `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepository.php` | Persistence adapter must own accessibility filtering per ADR-0008. | High |
| `src/Boardly/Projects/Application/ListProjects/ListProjectsHandler.php` | Default list currently asks for all owner projects and performs no active-only repository filtering. | High |
| `src/Boardly/Projects/Application/GetProject/GetProjectHandler.php` | Handler still does owner checks after a generic repository fetch. | High |
| `src/Boardly/Projects/Application/ArchiveProject/ArchiveProjectHandler.php` | Handler still does owner checks after a generic repository fetch. | High |
| `src/Boardly/Projects/Application/DeleteProject/DeleteProjectHandler.php` | Handler still does owner checks after a generic repository fetch. | High |
| `src/Boardly/Projects/Application/CreateProject/CreateProjectHandler.php` | Create result currently returns only `id` and `status`. | High |
| `src/Boardly/Projects/Application/CreateProject/CreateProjectResult.php` | Result shape must expand to the stable project contract fields. | High |
| `src/Boardly/Projects/Interfaces/Http/Response/CreateProjectResponseDto.php` | HTTP DTO must expose the expanded payload. | High |
| `src/Boardly/Projects/Interfaces/Http/OpenApi/Schema/CreateProjectResponse.php` | OpenAPI schema must match the create response contract. | High |
| `tests/Boardly/Projects/Interfaces/Http/Controller/ProjectControllerTest.php` | API contract assertions need to prove both the access behavior and the create payload shape. | High |

## Files read

- `docs/tasks/issues-55/planning.md`
- `docs/tasks/issues-55/analysis.md`
- `docs/tasks/issues-55/implementation.md`
- `docs/tasks/issues-55/implementation-checklist.md`
- `docs/tasks/issues-55/review-fix-checklist.md`
- `src/Boardly/Projects/Application/CreateProject/CreateProjectHandler.php`
- `src/Boardly/Projects/Application/CreateProject/CreateProjectResult.php`
- `src/Boardly/Projects/Application/GetProject/GetProjectResult.php`
- `src/Boardly/Projects/Application/Port/ProjectRepositoryInterface.php`
- `src/Boardly/Projects/Application/ListProjects/ListProjectsHandler.php`
- `src/Boardly/Projects/Application/GetProject/GetProjectHandler.php`
- `src/Boardly/Projects/Application/ArchiveProject/ArchiveProjectHandler.php`
- `src/Boardly/Projects/Application/DeleteProject/DeleteProjectHandler.php`
- `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepository.php`
- `src/Boardly/Projects/Interfaces/Http/Response/GetProjectResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Response/ListProjectsResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Response/CreateProjectResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/OpenApi/Schema/GetProjectResponse.php`
- `src/Boardly/Projects/Interfaces/Http/OpenApi/Schema/CreateProjectResponse.php`
- `tests/Boardly/Projects/Application/CreateProject/CreateProjectHandlerTest.php`
- `tests/Boardly/Projects/Application/ListProjects/ListProjectsHandlerTest.php`
- `tests/Boardly/Projects/Application/GetProject/GetProjectHandlerTest.php`
- `tests/Boardly/Projects/Application/ArchiveProject/ArchiveProjectHandlerTest.php`
- `tests/Boardly/Projects/Application/DeleteProject/DeleteProjectHandlerTest.php`
- `tests/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepositoryIntegrationTest.php`
- `tests/Boardly/Projects/Interfaces/Http/Controller/ProjectControllerTest.php`

## Facts learned

- The live review scope is tracked in `review-fix-checklist.md`, not in the older planning/analysis artifacts.
- ADR-0008 explicitly says repositories must not return inaccessible project data and rely on callers to filter it later.
- The current repository port still exposes generic `find` and `findByOwner`, and the handlers enforce ownership after fetching.
- The current create-project result/DTO/OpenAPI contract only returns `{ id, status }`, while the stable read contract shape is `{ id, name, iconKey, status, createdAt, updatedAt, archivedAt }`.
- The current working tree does not show a tracked `.env.test` modification; only `docs/tasks/issues-55/review-fix-checklist.md` was dirty before this pass.

## Still unknown

- Whether the parallel subagents will return identical or narrower file changes before the main-thread patch lands.

## Recommended next skill

- `task-implementation`
