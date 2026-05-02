# Issue #1 Analysis: Minimal Boardly Source Skeleton

## Issue Summary

GitHub issue #1 requests a minimal Boardly source skeleton according to ADR-0002.

This task is analysis and planning only. The source skeleton must not be implemented yet.

The later implementation is expected to create a passive directory skeleton for these initial Boardly areas:

- `Boardly/SharedKernel`
- `Boardly/Issues`
- `Boardly/Projects`
- `Boardly/Workflow`
- `Shared`

No domain behavior, Symfony controllers, Doctrine entities, repositories, migrations, queues, cache logic, search indexes, or service configuration should be introduced by this issue.

## Required Documents Reviewed

- `AGENTS.md`
- `docs/architecture/project-architecture-rules.md`
- `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
- `docs/adr/0002-use-boardly-context-based-source-structure.md`
- `docs/agents/subagents-map.md`
- `composer.json`
- `symfony.lock`
- `config/services.yaml`
- `config/routes.yaml`
- `config/packages/doctrine.yaml`
- `config/packages/messenger.yaml`
- `phpunit.xml.dist`
- `phpstan.neon`
- `Makefile`
- current `src/` structure
- current `tests/` structure
- existing CI location check

Sensitive files such as `.env`, `.env.local`, private keys, tokens, SQL dumps, and production credentials were not read.

## Relevant ADR-0001 Findings

ADR-0001 accepts Boardly as a Symfony-based Modular Monolith using Hexagonal Architecture and DDD.

Relevant constraints:

- Symfony is an implementation framework, not the architecture center.
- Business modules should be organized around domain responsibility, not Symfony folder conventions.
- Dependency direction is `Interfaces / UI -> Application -> Domain`; Infrastructure depends inward through Application or Domain ports.
- Domain must not depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, or HTTP.
- The relational database is the source of truth.
- Redis, RabbitMQ, and OpenSearch are supporting infrastructure only.
- RabbitMQ must not be required for core state changes to be valid.
- Microservices are explicitly out of scope unless a later ADR accepts them.

Issue #1 is compatible with ADR-0001 if it remains a directory-only skeleton and does not introduce framework-driven behavior or infrastructure dependencies.

## Relevant ADR-0002 Findings

ADR-0002 accepts this source structure direction:

```text
src/
├── Boardly/
│   ├── SharedKernel/
│   ├── Issues/
│   ├── Projects/
│   └── Workflow/
└── Shared/
```

Relevant placement rules:

- `src/Boardly` is for product-specific Boardly code.
- `src/Boardly/SharedKernel` is for small, stable, intentionally shared Boardly domain concepts.
- `src/Shared` is for technical or architectural abstractions that do not know Boardly domain language.
- Real bounded contexts may use `Domain`, `Application`, `Infrastructure`, and `Interfaces` layers.
- Do not create future modules just because they are listed as candidates.
- Do not create empty DDD folder structures just to look architectural.

There is a real tension: ADR-0002 warns against decorative empty modules, while issue #1 explicitly asks for a minimal skeleton. The planning interpretation is that issue #1 is a narrow repository bootstrap task tied to accepted ADR-0002 structure and the first deep scenario direction, not permission to create every future Boardly context.

## Relevant Architecture Rules Findings

The architecture rules reinforce:

- Boardly is a Jira-like project/task/workflow management system, not a CRM.
- Default architecture is Symfony, Modular Monolith, DDD, Hexagonal Architecture, CQRS where useful, and EDA only where async side effects are justified.
- DB remains source of truth.
- Redis is cache or fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ/Symfony Messenger is for asynchronous side effects, not core consistency.
- Controllers must stay thin.
- Doctrine listeners must not hide important business mutations.
- Avoid generic `Manager`, `Helper`, `BaseService`, and `AbstractService` classes.

Issue #1 should not introduce any CQRS, EDA, Redis, search, workflow, permission, or persistence behavior. It should only reserve the accepted source locations.

## Current Repository Structure Observations

Current `src/` directories:

```text
src
src/Controller
src/Entity
src/Interfaces
src/Interfaces/Http
src/Interfaces/Http/Controller
src/Repository
```

Current `src/` files:

- `src/Kernel.php`
- `src/Interfaces/Http/Controller/HealthCheckController.php`
- `src/Controller/.gitignore`
- `src/Entity/.gitignore`
- `src/Repository/.gitignore`

Current target-directory answer: the repository does not currently contain `src/Boardly`, `src/Boardly/SharedKernel`, `src/Boardly/Issues`, `src/Boardly/Projects`, `src/Boardly/Workflow`, or `src/Shared`.

The existing `src/Controller`, `src/Entity`, and `src/Repository` folders appear to be Symfony recipe placeholders. Removing or reorganizing them is outside issue #1 and should not be coupled to the skeleton implementation.

## Current Tests Structure Observations

Current `tests/` directories:

```text
tests
tests/Interfaces
tests/Interfaces/Http
tests/Interfaces/Http/Controller
```

Current test files:

- `tests/bootstrap.php`
- `tests/Interfaces/Http/Controller/HealthCheckControllerTest.php`

No tests currently exist for Boardly domain, application, infrastructure, or context-specific behavior.

## Current Composer Autoload Observations

`composer.json` has:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

and:

```json
"autoload-dev": {
    "psr-4": {
        "App\\Tests\\": "tests/"
    }
}
```

This already supports future namespaces such as:

- `App\Boardly\Issues\Domain`
- `App\Boardly\Projects\Application`
- `App\Boardly\Workflow\Infrastructure`
- `App\Boardly\SharedKernel\Domain`
- `App\Shared\Application`

Composer/autoload answer: no Composer autoload change is required for empty skeleton directories or future PHP classes under the planned layout, because `App\\` already maps to `src/`.

Empty-directory answer: empty skeleton directories do not require Composer autoload changes. `composer dump-autoload` is still a useful verification command after implementation, but it should not be expected to change behavior if only `.gitkeep` files are added.

## Current Symfony Service Discovery and Container Observations

`config/services.yaml` currently scans all of `src/`:

```yaml
App\:
    resource: '../src/'
    exclude:
        - '../src/Kernel.php'
```

Empty-directory service discovery answer: empty directories and `.gitkeep` files do not create Symfony services and should not affect container compilation.

Service discovery risk: if later implementation adds placeholder PHP classes under `Domain`, `Application`, `Infrastructure`, or `Interfaces`, Symfony may attempt to autowire/autoconfigure them because the whole `src/` tree is scanned. That is another reason issue #1 should use `.gitkeep` placeholders only and avoid placeholder PHP classes.

Future service exclusions may be needed when real value objects, domain events, DTOs, Doctrine entities, or generated classes are introduced. That is out of scope for issue #1.

## Current Routing Observations

`config/routes.yaml` currently loads attribute routes only from:

```yaml
controllers:
    resource:
        path: ../src/Interfaces/Http/Controller/
        namespace: App\Interfaces\Http\Controller
    type: attribute
```

The planned empty skeleton does not affect routing.

Future context-specific controllers under `src/Boardly/*/Interfaces` will not be discovered by the current route config until routing is intentionally changed. That should be planned with the first real controller/use case, not with issue #1.

## Current Doctrine and Messenger Observations

`config/packages/doctrine.yaml` enables auto mapping for `App` over the whole `src` tree:

```yaml
mappings:
    App:
        type: attribute
        is_bundle: false
        dir: '%kernel.project_dir%/src'
        prefix: 'App'
        alias: App
```

Empty directories and `.gitkeep` files should not affect Doctrine.

Risk appears only if placeholder PHP classes are created and accidentally marked or interpreted as Doctrine-managed classes later. No Doctrine entities or mapping decisions should be introduced in issue #1.

`config/packages/messenger.yaml` defines async and failed transports but no routing. Issue #1 should not modify Messenger configuration or introduce messages/handlers.

## Current Tests, CI, and Makefile Observations

`phpunit.xml.dist`:

- boots `tests/bootstrap.php`;
- runs tests under `tests`;
- includes `src` as source.

`phpstan.neon`:

- level `6`;
- scans `src` and `tests`.

PHPUnit/PHPStan answer: `.gitkeep` files under `src` should not affect PHPUnit or PHPStan because they are non-PHP files. Placeholder PHP classes would carry more risk because they could be scanned by PHPStan and discovered by Symfony service autowiring.

Makefile targets relevant to later verification:

- `cc`
- `container`
- `local-test`
- `local-phpstan`
- `test`
- `phpstan`
- `qa`
- `local-qa`

No `.github` CI configuration directory is present in the local checkout.

Local `git status` and `git branch` could not be inspected because the machine reports an Xcode command line tools error:

```text
xcrun: error: invalid active developer path
```

This does not block writing the planning docs, but it prevented local Git status verification.

## Scope Confirmation

In scope for the later implementation phase:

- create the exact issue-requested source directories;
- add lightweight placeholder files so Git tracks the empty directories;
- prefer `.gitkeep` placeholders;
- avoid Composer, Symfony, Doctrine, Messenger, Redis, OpenSearch, and test changes unless verification reveals an unexpected issue.

In scope for this current phase:

- inspect repository files and required docs;
- create or update `docs/tasks/issues-1/analysis.md`;
- create or update `docs/tasks/issues-1/plan.md`.

## Non-Goals Confirmation

Not in scope:

- creating the source skeleton now;
- adding business logic;
- adding aggregates, entities, value objects, repositories, commands, handlers, controllers, routes, migrations, or Doctrine mappings;
- changing Messenger configuration;
- adding Redis or OpenSearch logic;
- introducing Boards, Notifications, Search, Audit, Reporting, Attachments, Integrations, Automation, or other future modules;
- introducing microservices;
- creating generic `Manager`, `Helper`, `BaseService`, or framework-driven layers;
- removing existing Symfony recipe placeholder folders.

## Risks

- ADR-0002 warns against decorative empty modules; issue #1 must be treated as a narrow bootstrap exception, not a general permission to pre-create future contexts.
- Empty context folders may be mistaken for completed bounded-context design.
- `src/Shared` may later become a dumping ground for Boardly domain concepts.
- `Boardly/SharedKernel` may grow before shared domain concepts are stable.
- Placeholder PHP classes would create Symfony service discovery, PHPStan, and namespace risks.
- Current route discovery does not include future context-specific `Interfaces` folders.
- Current Doctrine mapping scans `src`, so real entity mapping strategy must be handled carefully in a later task.

## Open Questions

- Should the later implementation include `.gitkeep` files only in leaf directories, or also in parent context directories? Leaf `.gitkeep` files are sufficient for Git to track the full tree and should be preferred.
- Should existing Symfony recipe placeholder folders `src/Controller`, `src/Entity`, and `src/Repository` be removed later? This is a separate cleanup decision, not issue #1.
- When real controllers are introduced, should routes be discovered from context-specific `Interfaces/Http/Controller` paths? This should be decided with the first real controller/use case.
- When real persistent aggregates are introduced, should Doctrine use attributes, XML, or PHP mapping? ADR-0002 leaves this open for a later decision.

## Explicit Answers

- Does the current repository already contain some target directories? No. None of `src/Boardly/...` or `src/Shared/...` exists currently.
- Does `composer.json` already support the planned namespace layout? Yes. `App\\` maps to `src/`, so `App\Boardly\...` and `App\Shared\...` are already covered.
- Will empty skeleton directories require Composer autoload changes? No.
- Are there Symfony service discovery risks from empty directories? No direct risk from empty directories or `.gitkeep`; risk begins if placeholder PHP classes are added under the globally scanned `src/` tree.
- Are there PHPUnit/PHPStan risks from `.gitkeep` files? No meaningful risk; they are non-PHP files. Placeholder PHP classes would be riskier.
- Are there conflicts with ADR-0001, ADR-0002, or architecture rules? There is no conflict with ADR-0001 or the architecture rules if the change is directory-only. There is a tension with ADR-0002's warning against decorative empty modules; the mitigation is to treat issue #1 as a narrow ADR-0002 bootstrap skeleton and avoid extra modules or placeholder classes.
