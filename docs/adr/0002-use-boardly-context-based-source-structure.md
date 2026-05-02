# ADR-0002: Use Boardly Context-Based Source Structure

## Status

Accepted

## Date

2026-05-02

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / Symfony owner

## Context

ADR-0001 established Boardly as a Symfony-based Modular Monolith using Hexagonal Architecture and DDD.

The next required decision is the source code structure under `src/`.

Boardly is not a simple CRUD application. It will contain multiple product areas and bounded context candidates:

- Identity & Access;
- Projects;
- Issues;
- Workflow;
- Boards;
- Collaboration;
- Notifications;
- Search;
- Reporting;
- Audit;
- Integrations;
- Automation.

We need a structure that supports business-oriented modules, preserves Hexagonal Architecture boundaries, and avoids making Symfony folders the center of the application.

At the same time, we must avoid over-engineering the project by creating many empty modules before real use cases exist.

The structure must answer these questions:

- where product-specific Boardly code should live;
- where bounded contexts should live;
- where shared product-domain concepts should live;
- where technical reusable abstractions should live;
- how to avoid turning `Shared` into a dumping ground;
- how to keep Domain independent from Symfony and infrastructure.

## Decision

We will organize source code using a context-based structure under `src/`.

The main structure is:

```text
src/
├── Boardly/
│   ├── SharedKernel/
│   ├── IdentityAccess/
│   ├── Projects/
│   ├── Issues/
│   ├── Workflow/
│   └── Boards/
│
└── Shared/
```

`src/Boardly` contains product-specific code that speaks the Boardly business language.

`src/Boardly/SharedKernel` contains small shared domain concepts that are specific to Boardly and intentionally shared across bounded contexts.

`src/Shared` contains technical or architectural abstractions that do not know anything about Boardly domain concepts.

Each real Boardly bounded context may follow this internal structure:

```text
<Context>/
├── Domain/
├── Application/
├── Infrastructure/
└── Interfaces/
```

But these folders should be created only when there is a real use case or implementation need.

We will not create all possible future modules upfront.

## Scope

This decision applies to:

- PHP source code under `src/`;
- business module organization;
- bounded context folder structure;
- shared domain concepts;
- shared technical abstractions;
- Symfony service organization;
- future module additions.

This decision does not apply to:

- frontend source structure;
- Docker and infrastructure folder structure;
- documentation folder structure;
- test structure in full detail;
- final deployment structure.

## Architectural Rules

- Product-specific code must live under `src/Boardly`.
- Code that speaks the Boardly domain language must not live under generic `src/Shared`.
- `src/Boardly/SharedKernel` is allowed only for small, stable, shared Boardly domain concepts.
- `src/Shared` is only for technical or architectural abstractions that are not specific to Boardly.
- Every bounded context should be organized by business responsibility, not by framework mechanism.
- A bounded context may contain `Domain`, `Application`, `Infrastructure`, and `Interfaces` layers.
- Do not create modules before a real use case exists.
- Do not create empty DDD folder structures just to look architectural.
- Domain code must not depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, or HTTP.
- Infrastructure code may depend on Symfony components and external libraries.
- Interfaces / UI code may contain HTTP controllers, CLI commands, request DTOs, and response presenters.
- Controllers must stay thin and call Application commands or queries.
- Shared abstractions must be reviewed carefully before being added.
- Avoid generic `Manager`, `Helper`, `Utils`, `BaseService`, and `AbstractService` classes.

## Proposed Source Structure

Initial recommended structure:

```text
src/
├── Kernel.php
│
├── Boardly/
│   ├── SharedKernel/
│   │   └── Domain/
│   │       ├── UserId.php
│   │       ├── ProjectId.php
│   │       ├── IssueId.php
│   │       └── DomainEvent.php
│   │
│   ├── IdentityAccess/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Interfaces/
│   │
│   ├── Projects/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Interfaces/
│   │
│   ├── Issues/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Interfaces/
│   │
│   └── Workflow/
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       └── Interfaces/
│
└── Shared/
    ├── Domain/
    │   ├── Clock/
    │   ├── Exception/
    │   └── ValueObject/
    │
    ├── Application/
    │   ├── Command/
    │   ├── Query/
    │   ├── Transaction/
    │   └── Event/
    │
    └── Infrastructure/
        ├── Doctrine/
        ├── Messenger/
        ├── Symfony/
        └── Persistence/
```

This is the target direction, not a requirement to create every folder immediately.

The first actual modules should be created only when needed for the first deep scenario, likely `ChangeIssueStatus`.

## Classification Rules

### `src/Boardly`

Use `src/Boardly` for all product-specific business code.

Examples:

```text
Issue
Project
Workflow
Board
Comment
Assignee
Reporter
Transition
Permission
ProjectRole
IssuePriority
IssueStatus
IssueType
```

Examples of valid namespaces:

```text
App\Boardly\Issues\Domain\Issue
App\Boardly\Projects\Domain\Project
App\Boardly\Workflow\Domain\Workflow
App\Boardly\Issues\Application\ChangeStatus\ChangeIssueStatusCommand
```

### `src/Boardly/SharedKernel`

Use `src/Boardly/SharedKernel` for small shared domain concepts that belong to Boardly and are intentionally shared across contexts.

Examples:

```text
UserId
ProjectId
IssueId
TeamId
DomainEvent
```

Rules:

- Shared Kernel must stay small.
- Shared Kernel must not contain application services.
- Shared Kernel must not contain infrastructure adapters.
- Shared Kernel must not contain generic helpers.
- A class should enter Shared Kernel only if at least two real contexts need the same domain concept.

### `src/Shared`

Use `src/Shared` for technical abstractions that do not know about Boardly.

Examples:

```text
CommandBusInterface
QueryBusInterface
TransactionalSessionInterface
ClockInterface
UuidGeneratorInterface
DoctrineTransactionalSession
MessengerCommandBus
SymfonyClock
```

A class belongs in `src/Shared` only if it could reasonably be reused in another Symfony application without knowing anything about issues, projects, workflows, boards, or permissions.

## Alternatives Considered

### Option A: Flat Symfony Structure

Example:

```text
src/
├── Controller/
├── Entity/
├── Repository/
├── Service/
└── Message/
```

Pros:

- Simple and familiar to many Symfony developers.
- Fast for CRUD prototypes.
- Low initial ceremony.

Cons:

- Organizes code by technical role instead of business responsibility.
- Encourages business logic in controllers, services, and Doctrine entities.
- Makes bounded contexts unclear.
- Does not scale well for workflow, permissions, audit, search, and async side effects.
- Makes future modularization harder.

### Option B: Contexts Directly Under `src/`

Example:

```text
src/
├── Issues/
├── Projects/
├── Workflow/
└── Shared/
```

Pros:

- Short namespaces.
- Simple directory depth.
- Business modules are visible immediately.

Cons:

- No clear distinction between product-specific code and generic technical abstractions.
- `Shared` may become ambiguous.
- Harder to separate Boardly product code from application-wide technical primitives.
- Less explicit product namespace.

### Option C: `src/Boardly/<Context>` plus `src/Shared`

Example:

```text
src/
├── Boardly/
│   ├── Issues/
│   ├── Projects/
│   ├── Workflow/
│   └── SharedKernel/
└── Shared/
```

Pros:

- Clear separation between product domain and technical shared code.
- Bounded contexts are grouped under the product namespace.
- Shared Kernel has a precise meaning.
- Supports Modular Monolith and Hexagonal Architecture.
- Reduces risk of generic `Shared` becoming a domain dumping ground.
- Leaves room for reusable infrastructure abstractions.

Cons:

- Slightly deeper namespaces.
- Requires discipline when deciding what belongs in `Shared` vs `Boardly/SharedKernel`.
- Can still become over-engineered if empty modules are created too early.

## Consequences

### Positive

- The source tree reflects business boundaries.
- Product-specific code is clearly separated from reusable technical code.
- Shared Kernel is explicit and intentionally small.
- Contexts can evolve independently inside one deployable application.
- The structure supports future extraction better than flat Symfony folders.
- Symfony remains an implementation detail.

### Negative

- Namespaces are longer.
- Initial contributors need to understand the difference between `Shared`, `Boardly`, and `Boardly/SharedKernel`.
- Some small features may require more placement decisions.
- Incorrect use of `Shared` can still create hidden coupling.

### Neutral / Operational

- Deployment remains a single Symfony application.
- Composer autoload can continue using the `App\` namespace.
- Symfony service discovery can scan `src/` but exclusions may need to be refined later.
- Doctrine mapping configuration may need explicit per-context configuration when real entities are introduced.

## Trade-offs

- We accept deeper namespaces in exchange for clearer product and technical boundaries.
- We accept explicit placement rules in exchange for avoiding a generic `Service` or `Shared` dumping ground.
- We accept not creating every future module now in exchange for avoiding decorative architecture.
- We accept that some shared concepts may move later after real duplication proves the need.

## Impact on Layers

### Domain

- Domain code lives inside each Boardly context under `Domain`.
- Shared Boardly domain concepts live under `Boardly/SharedKernel/Domain`.
- Generic domain-independent abstractions may live under `Shared/Domain`.
- Domain code remains framework-independent.

### Application

- Application use cases live under each context's `Application` layer.
- Shared command/query/transaction abstractions may live under `Shared/Application`.
- Application code orchestrates use cases and depends on Domain.

### Infrastructure

- Context-specific persistence, messaging, search, cache, and external adapters live under each context's `Infrastructure` layer.
- Reusable infrastructure adapters may live under `Shared/Infrastructure`.
- Infrastructure may depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, and external libraries.

### Interfaces / UI

- Context-specific controllers, CLI commands, webhook adapters, request DTOs, and presenters live under each context's `Interfaces` layer when they belong to that context.
- Existing generic technical controllers such as health checks may remain outside Boardly contexts if they are not product-specific.

## Impact on DDD

This decision supports DDD by placing bounded contexts under `src/Boardly`.

Initial bounded context candidates should not all be created immediately.

Start with contexts needed for real scenarios:

- Issues;
- Projects;
- Workflow;
- IdentityAccess if permission checks are needed.

`Boardly/SharedKernel` should contain only stable concepts shared across contexts.

Potential Shared Kernel concepts:

- `UserId`;
- `ProjectId`;
- `IssueId`;
- `TeamId`;
- base `DomainEvent` contract.

Do not put aggregates like `Issue`, `Project`, or `Workflow` into Shared Kernel.

Do not put services like `IssueHelper`, `ProjectManager`, or `PermissionUtils` into Shared Kernel.

## Impact on EDA

Domain events belong to the context where the business fact happens.

Examples:

```text
App\Boardly\Issues\Domain\Event\IssueCreated
App\Boardly\Issues\Domain\Event\IssueStatusChanged
App\Boardly\Projects\Domain\Event\ProjectArchived
```

Shared event interfaces or base contracts may live under:

```text
App\Boardly\SharedKernel\Domain\Event
```

Technical event dispatching infrastructure may live under:

```text
App\Shared\Infrastructure\Messenger
```

or context-specific infrastructure if the adapter is not reusable.

This ADR does not introduce Outbox Pattern. Outbox usage must be decided separately when reliable event publication becomes required.

## Impact on CQRS

Commands and queries should live close to the context they belong to.

Examples:

```text
App\Boardly\Issues\Application\ChangeStatus\ChangeIssueStatusCommand
App\Boardly\Issues\Application\ChangeStatus\ChangeIssueStatusHandler
App\Boardly\Issues\Application\GetDetails\GetIssueDetailsQuery
App\Boardly\Projects\Application\Create\CreateProjectCommand
```

Shared command bus and query bus contracts may live under:

```text
App\Shared\Application\Command
App\Shared\Application\Query
```

Read models should live in the context that owns the read use case unless they are explicitly cross-context projections.

## Symfony Implementation Direction

Composer autoload can remain simple:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

Symfony service discovery may continue scanning `src/` initially.

Later, service configuration may need explicit exclusions for:

- Doctrine entities if required;
- value objects;
- domain events;
- DTOs;
- test fixtures;
- generated code.

Routes should be loaded from context-specific `Interfaces` layers when real controllers are introduced.

Example future direction:

```yaml
controllers:
  resource: '../src/Boardly/*/Interfaces/Http/Controller/'
  type: attribute
```

Doctrine mapping should be decided separately before creating real domain entities.

Open question remains whether mappings should use attributes, XML, or PHP configuration.

## Risks

- `src/Shared` becomes a dumping ground.
- `Boardly/SharedKernel` grows too large and couples bounded contexts.
- Empty modules are created before real use cases exist.
- Developers put product-domain classes into generic `Shared`.
- Developers put technical abstractions into `Boardly/SharedKernel`.
- Long namespaces reduce readability if folders are nested too deeply.
- Context boundaries are guessed too early and later become expensive to change.

## Mitigations

- Require a real use case before creating a new module.
- Keep `SharedKernel` small and review every addition.
- Use the classification rule: if it speaks Boardly language, it belongs under `Boardly`.
- Use `Shared` only for Boardly-independent technical abstractions.
- Prefer moving code later over guessing all boundaries upfront.
- Use ADRs for major context boundary decisions.
- Start structure with the first deep scenario: `ChangeIssueStatus`.

## Migration / Adoption Plan

1. Keep existing Symfony technical bootstrap.
2. Create only the source directories needed for the first real business scenario.
3. Start with `Boardly/Issues`, `Boardly/Projects`, `Boardly/Workflow`, and possibly `Boardly/IdentityAccess` if permissions are part of the first use case.
4. Add `Boardly/SharedKernel` only when shared Boardly domain concepts are actually needed.
5. Add `Shared` only when a technical abstraction is needed by more than one place or improves architecture clarity.
6. Adjust Symfony route and service configuration only when real controllers/services are moved into the new structure.
7. Revisit Doctrine mapping strategy before implementing the first persistent aggregate.

## Open Questions

- Should `Boards` be created immediately or only after board-specific use cases appear?
- Should `IdentityAccess` be created before or during the first permission-sensitive scenario?
- Should `Workflow` be a separate bounded context from the start, or should simple workflow behavior start inside `Issues`?
- Should Doctrine mappings use attributes, XML, or PHP configuration?
- Should synchronous command/query dispatch use Symfony Messenger immediately or explicit handler wiring first?
- Should `DomainEvent` live in `Boardly/SharedKernel` or `Shared/Domain/Event`?

## References

- Related ADRs:
  - `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
- Related docs:
  - `docs/architecture/project-architecture-rules.md`
  - `docs/agents/subagents-map.md`
  - `docs/adr/0000-template.md`
- Related issues: none
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
