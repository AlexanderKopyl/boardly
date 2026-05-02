# Issue #1 Plan: Minimal Boardly Source Skeleton

## Summary

Implement a passive source skeleton that matches the issue-requested ADR-0002 layout without adding business behavior or runtime configuration.

This plan is for the later implementation phase. The source skeleton is not implemented by this planning task.

## Proposed Implementation Strategy

Use `.gitkeep` files in leaf directories to make Git track the empty skeleton.

Do not add placeholder PHP classes or placeholder documentation files inside `src/`. `.gitkeep` is preferable because it has no Composer, Symfony container, Doctrine, PHPUnit, or PHPStan behavior.

Do not modify:

- `composer.json`
- `config/services.yaml`
- `config/routes.yaml`
- `config/packages/doctrine.yaml`
- `config/packages/messenger.yaml`
- `phpunit.xml.dist`
- `phpstan.neon`
- `Makefile`

Frame the implementation as a narrow bootstrap of ADR-0002's accepted source structure. Do not use it as precedent for adding unrelated future modules.

## Exact Directories to Create Later

Create exactly:

```text
src/Boardly/SharedKernel/Domain/
src/Boardly/Issues/Domain/
src/Boardly/Issues/Application/
src/Boardly/Issues/Infrastructure/
src/Boardly/Issues/Interfaces/
src/Boardly/Projects/Domain/
src/Boardly/Projects/Application/
src/Boardly/Projects/Infrastructure/
src/Boardly/Projects/Interfaces/
src/Boardly/Workflow/Domain/
src/Boardly/Workflow/Application/
src/Boardly/Workflow/Infrastructure/
src/Boardly/Workflow/Interfaces/
src/Shared/Domain/
src/Shared/Application/
src/Shared/Infrastructure/
```

Do not create:

- `src/Boardly/Boards`
- `src/Boardly/IdentityAccess`
- `src/Boardly/Notifications`
- `src/Boardly/Search`
- `src/Boardly/Audit`
- `src/Boardly/Reporting`
- `src/Boardly/Attachments`
- `src/Boardly/Integrations`
- `src/Boardly/Automation`
- any other future module not requested by issue #1.

## Exact Placeholder Files to Create Later

Create exactly these placeholder files:

```text
src/Boardly/SharedKernel/Domain/.gitkeep
src/Boardly/Issues/Domain/.gitkeep
src/Boardly/Issues/Application/.gitkeep
src/Boardly/Issues/Infrastructure/.gitkeep
src/Boardly/Issues/Interfaces/.gitkeep
src/Boardly/Projects/Domain/.gitkeep
src/Boardly/Projects/Application/.gitkeep
src/Boardly/Projects/Infrastructure/.gitkeep
src/Boardly/Projects/Interfaces/.gitkeep
src/Boardly/Workflow/Domain/.gitkeep
src/Boardly/Workflow/Application/.gitkeep
src/Boardly/Workflow/Infrastructure/.gitkeep
src/Boardly/Workflow/Interfaces/.gitkeep
src/Shared/Domain/.gitkeep
src/Shared/Application/.gitkeep
src/Shared/Infrastructure/.gitkeep
```

Do not create PHP placeholder files such as `README.php`, `IssueService.php`, `DomainEvent.php`, `Project.php`, or `Workflow.php`.

## Composer / Autoload Impact

No Composer autoload change is expected.

Current autoload already maps:

```json
"App\\": "src/"
```

That supports all later namespaces under:

- `App\Boardly\SharedKernel\Domain`
- `App\Boardly\Issues`
- `App\Boardly\Projects`
- `App\Boardly\Workflow`
- `App\Shared`

Run `composer dump-autoload` during the later implementation phase as a sanity check. It is not necessary for this planning-only phase because no source skeleton is being created now.

## Symfony Container Impact

No Symfony container change is expected if the implementation only creates directories and `.gitkeep` files.

`config/services.yaml` currently scans `src/`, so future PHP classes can become services automatically. This is a risk for placeholder PHP classes, not for `.gitkeep`.

Run `php bin/console cache:clear` and `php bin/console debug:container` during the later implementation phase to confirm the empty skeleton does not affect container compilation or service registration.

Makefile equivalents that exist:

```bash
make cc
make container
```

## Routing Impact

No routing change is expected.

Current route discovery points to `src/Interfaces/Http/Controller`. The future `src/Boardly/*/Interfaces` directories should remain empty in issue #1 and should not create routes.

Route discovery for context-specific controllers should be handled later when a real controller is introduced.

## Doctrine Impact

No Doctrine mapping change is expected.

Doctrine currently scans `src` for attribute mapping under prefix `App`. `.gitkeep` files do not matter. Real Doctrine mapping strategy for Boardly aggregates/entities is out of scope and should be decided before persistent domain classes are added.

## Test / Verification Plan

This planning phase does not need to run implementation verification commands because it does not create the source skeleton.

During the later implementation phase, run:

```bash
composer dump-autoload
php bin/console cache:clear
php bin/console debug:container
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

Use Makefile targets that exist as alternatives:

```bash
make cc
make container
make local-test
make local-phpstan
```

Expected verification results:

- Composer autoload generation succeeds.
- Symfony cache clears successfully.
- Symfony container debug runs successfully.
- PHPUnit remains green, including the existing health check test.
- PHPStan remains green.
- No new services, routes, migrations, queues, cache keys, search indexes, or runtime dependencies are introduced by the skeleton.

Optional manual checks:

```bash
find src/Boardly src/Shared -type f | sort
find src/Boardly src/Shared -type d | sort
```

These should show only the planned directories and `.gitkeep` files.

## Rollback Plan

If verification fails after the later skeleton implementation:

1. Confirm no PHP placeholder files were accidentally added.
2. Confirm no Symfony, Doctrine, Messenger, route, Composer, PHPUnit, or PHPStan configuration was changed.
3. Remove only the newly added `src/Boardly/...` and `src/Shared/...` `.gitkeep` files and empty directories.
4. Re-run `composer dump-autoload`, `php bin/console cache:clear`, `vendor/bin/phpunit`, and `vendor/bin/phpstan analyse`.

Do not remove existing Symfony recipe folders as part of rollback unless a separate task explicitly scopes that cleanup.

## Explicit Non-Goals

Do not implement:

- aggregates;
- entities;
- value objects;
- repositories;
- commands;
- command handlers;
- query handlers;
- controllers;
- route configuration;
- Doctrine mappings;
- database migrations;
- Messenger messages or handlers;
- Redis logic;
- OpenSearch/Elasticsearch logic;
- workflow rules;
- permission rules;
- audit logic;
- future modules beyond Issues, Projects, Workflow, SharedKernel, and Shared.

Do not create generic:

- `Manager`
- `Helper`
- `BaseService`
- `AbstractService`
- `Utils`

## Acceptance Criteria Checklist

- [ ] Only the exact planned directories are created.
- [ ] Each planned leaf directory contains a `.gitkeep` file.
- [ ] No PHP placeholder classes are created.
- [ ] No business logic is introduced.
- [ ] No future modules are introduced.
- [ ] `composer.json` is unchanged.
- [ ] Symfony service and route configuration are unchanged.
- [ ] Doctrine and Messenger configuration are unchanged.
- [ ] PHPUnit and PHPStan configuration are unchanged.
- [ ] `composer dump-autoload` succeeds.
- [ ] `php bin/console cache:clear` succeeds.
- [ ] `php bin/console debug:container` succeeds.
- [ ] `vendor/bin/phpunit` succeeds.
- [ ] `vendor/bin/phpstan analyse` succeeds.
- [ ] The implementation summary references ADR-0001 and ADR-0002.
- [ ] The implementation summary states that this is a passive bootstrap skeleton, not completed domain design.

## Step-by-Step Implementation Checklist for Later

1. Re-read `docs/tasks/issues-1/analysis.md`.
2. Confirm no newer ADR supersedes ADR-0001 or ADR-0002.
3. Confirm `src/Boardly` and `src/Shared` do not already exist, or inspect any existing contents before modifying them.
4. Create `src/Boardly/SharedKernel/Domain/`.
5. Create `src/Boardly/Issues/{Domain,Application,Infrastructure,Interfaces}/`.
6. Create `src/Boardly/Projects/{Domain,Application,Infrastructure,Interfaces}/`.
7. Create `src/Boardly/Workflow/{Domain,Application,Infrastructure,Interfaces}/`.
8. Create `src/Shared/{Domain,Application,Infrastructure}/`.
9. Add `.gitkeep` to each leaf directory listed in this plan.
10. Confirm no PHP classes or config files were added.
11. Run `composer dump-autoload`.
12. Run `php bin/console cache:clear` or `make cc`.
13. Run `php bin/console debug:container` or `make container`.
14. Run `vendor/bin/phpunit` or `make local-test`.
15. Run `vendor/bin/phpstan analyse` or `make local-phpstan`.
16. Inspect the resulting file list to confirm only intended skeleton files were added.
17. Document in the implementation summary that the source skeleton implementation was completed without adding business behavior.
