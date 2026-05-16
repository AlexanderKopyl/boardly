# Implementation: GitHub issue #55: Start core Product domain with Projects MVP

## Status
- Phase: Phase 3: Infrastructure (Persistence)
- Task: Implementing Doctrine Persistence

## Subagents used
- `generalist`

## Skills used
- `task-implementation`
- `ddd-modeling`
- `testing-strategy`

## Files changed
- `src/Boardly/Projects/Application/Port/ProjectRepositoryInterface.php`
- `src/Boardly/Projects/Application/Exception/ProjectApplicationException.php`
- `src/Boardly/Projects/Application/Exception/ProjectNotFound.php`
- `src/Boardly/Projects/Application/CreateProject/CreateProjectCommand.php`
- `src/Boardly/Projects/Application/CreateProject/CreateProjectHandler.php`
- `src/Boardly/Projects/Application/CreateProject/CreateProjectResult.php`
- `src/Boardly/Projects/Application/ListProjects/ListProjectsQuery.php`
- `src/Boardly/Projects/Application/ListProjects/ListProjectsHandler.php`
- `src/Boardly/Projects/Application/ListProjects/ListProjectsResult.php`
- `src/Boardly/Projects/Application/ListProjects/ProjectListItem.php`
- `src/Boardly/Projects/Application/GetProject/GetProjectQuery.php`
- `src/Boardly/Projects/Application/GetProject/GetProjectHandler.php`
- `src/Boardly/Projects/Application/GetProject/GetProjectResult.php`
- `src/Boardly/Projects/Application/ArchiveProject/ArchiveProjectCommand.php`
- `src/Boardly/Projects/Application/ArchiveProject/ArchiveProjectHandler.php`
- `src/Boardly/Projects/Application/ArchiveProject/ArchiveProjectResult.php`
- `tests/Boardly/Projects/Application/CreateProject/CreateProjectHandlerTest.php`
- `tests/Boardly/Projects/Application/ListProjects/ListProjectsHandlerTest.php`
- `tests/Boardly/Projects/Application/GetProject/GetProjectHandlerTest.php`
- `tests/Boardly/Projects/Application/ArchiveProject/ArchiveProjectHandlerTest.php`

## Summary
Completed Phase 2: Application Layer.
- Created `ProjectRepositoryInterface` port.
- Implemented `CreateProject`, `ListProjects`, `GetProject`, and `ArchiveProject` use cases.
- Followed ADR-0008 by returning 404 instead of 403 for unauthorized project access in `GetProject` and `ArchiveProject`.
- Added unit tests with fakes for all use cases, achieving 100% coverage for the business logic.
- Verified all tests pass.

## Verification
- Ran `vendor/bin/phpunit tests/Boardly/Projects/Application/`
- Result: OK (7 tests, 26 assertions)



## Risks / follow-up
N/A

## 2026-05-16 10:29:17 EEST - Task: Phase 3: Infrastructure (Persistence)

### Subagents used
- `worker` (`project_persistence_implementation`)

### Skills used
- `task-implementation`
- `testing-strategy`

### Files changed
- `src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity/ProjectEntity.php`
- `migrations/Version20260516071731.php`
- `tests/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepositoryIntegrationTest.php`
- `docs/tasks/issues-55/implementation-checklist.md`

### Summary
- Added the Projects Doctrine integration test covering save/reload, owner filtering, and update/archive persistence.
- Aligned the Projects schema with ADR-0009 by adding the `owner_account_id` foreign key to `public.accounts` and constraining `status` to `VARCHAR(32)`.
- Updated the Doctrine entity to match the narrower `status` column.
- Marked the Phase 3 persistence checklist items complete because the repository, entity, mapping, migration, and tests are now present in the repo.

### Verification
- `php -l src/Boardly/Projects/Infrastructure/Persistence/Doctrine/Entity/ProjectEntity.php`
- `php -l migrations/Version20260516071731.php`
- `php -l tests/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepositoryIntegrationTest.php`
- `php bin/console doctrine:migrations:migrate --env=test --no-interaction`
- `php ./vendor/bin/phpunit tests/Boardly/Projects/Infrastructure/Persistence/Doctrine/Repository/DoctrineProjectRepositoryIntegrationTest.php`

### Risks / follow-up
- Phase 4 API work and final validation remain unchecked in the task folder.
