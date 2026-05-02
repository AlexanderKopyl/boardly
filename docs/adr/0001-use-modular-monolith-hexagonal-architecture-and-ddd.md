# ADR-0001: Use Modular Monolith, Hexagonal Architecture, and DDD

## Status

Accepted

## Date

2026-05-02

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / Symfony owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

The product must support:

- projects;
- issues / tasks;
- workflows;
- boards;
- permissions;
- collaboration;
- notifications;
- audit;
- search;
- reporting;
- automation.

The project is at the technical bootstrap stage. The repository already contains a Symfony skeleton, Docker-based local infrastructure, architecture rules, agent routing documentation, and ADR template.

We need a clear baseline architecture before creating business modules such as Issues, Projects, Workflow, Boards, and Permissions.

Without this decision, the project risks becoming framework-driven:

- controllers containing business logic;
- Doctrine entities becoming the entire domain model;
- module boundaries being created by technical folders instead of business responsibility;
- async messaging being used for core state changes;
- Redis or OpenSearch being treated as source of truth;
- premature microservice boundaries.

## Decision

We will build Boardly as a Symfony-based Modular Monolith using Hexagonal Architecture and DDD.

The default architectural model is:

```text
Interfaces / UI -> Application -> Domain
Infrastructure -> Application / Domain ports
```

The relational database is the source of truth.

RabbitMQ, Redis, and OpenSearch are supporting infrastructure and must not own canonical business state.

We will use CQRS where it makes command and query responsibilities clearer.

We will use Event-Driven Architecture for asynchronous side effects and decoupling, not for mandatory core state changes.

We will not split the system into microservices by default.

Microservices may be considered later only if there is a proven operational or business need and a separate ADR accepts that decision.

## Scope

This decision applies to:

- all backend business modules;
- all bounded contexts;
- all Symfony code structure decisions;
- all persistence adapters;
- all async messaging flows;
- all search and cache integrations;
- all future module boundaries.

This decision applies especially to these bounded context candidates:

- Identity & Access;
- Projects;
- Issues;
- Workflow;
- Boards;
- Collaboration;
- Attachments;
- Notifications;
- Search;
- Reporting;
- Audit;
- Integrations;
- Automation.

This decision does not apply to:

- frontend application architecture;
- production hosting provider selection;
- final CI/CD implementation;
- final observability stack;
- future extraction of services if a later ADR supersedes this decision.

## Architectural Rules

- Symfony is the implementation framework, not the architecture center.
- Business modules must be organized around domain responsibility, not around Symfony folders.
- Domain layer must not depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, or HTTP.
- Application layer orchestrates use cases but does not own core business invariants.
- Infrastructure adapters implement ports defined by Application or Domain.
- Interfaces / UI adapters deliver input and output and must stay thin.
- Controllers must not contain workflow rules, permission logic, or aggregate mutation logic.
- Doctrine repositories are infrastructure adapters unless a repository interface expresses a domain need.
- RabbitMQ must not be required for a core state change to be valid.
- Redis must not be used as the source of truth for canonical business state.
- OpenSearch must not be used as the source of truth for canonical business state.
- Search indexes and projections may be eventually consistent.
- Async handlers must be idempotent.
- Important cross-cutting architecture changes require an ADR.

## Alternatives Considered

### Option A: Classic Symfony MVC / CRUD Architecture

Pros:

- Faster initial CRUD development.
- Lower upfront structural complexity.
- Easier for simple admin-style applications.
- Aligns with many Symfony tutorials.

Cons:

- Business logic tends to leak into controllers, forms, Doctrine entities, and services.
- Complex workflow and permission logic becomes hard to reason about.
- Module boundaries are unclear.
- Async side effects can become hidden in Doctrine listeners or framework events.
- Harder to test domain behavior without framework bootstrapping.
- Harder to evolve into a large Jira-like product.

### Option B: Microservices from the Start

Pros:

- Strong deploy-time isolation.
- Independent scaling per service.
- Clear network boundaries between services.
- Can fit large organizations with separate teams.

Cons:

- Too much operational complexity for the current stage.
- Distributed transactions and consistency problems appear too early.
- Requires mature DevOps, observability, deployment, and incident response.
- Slows down domain discovery.
- Encourages premature service boundaries before the domain is understood.
- Increases testing and local development complexity.

### Option C: Modular Monolith with Hexagonal Architecture and DDD

Pros:

- Keeps deployment simple while preserving internal boundaries.
- Supports domain-driven module design.
- Keeps business rules independent from Symfony and infrastructure.
- Makes future extraction of modules possible if justified.
- Fits complex workflows, permissions, issue lifecycle, audit, search, and async side effects.
- Easier to test domain and application behavior.

Cons:

- More structure and discipline are required.
- More boilerplate than simple CRUD.
- Developers must understand layer boundaries.
- Wrong abstractions can still happen if DDD is used decoratively.

## Consequences

### Positive

- Business behavior can be modeled explicitly.
- Core rules can be tested without HTTP, Doctrine, RabbitMQ, Redis, or OpenSearch.
- Module boundaries can evolve with the domain.
- Infrastructure can be replaced or changed behind adapters.
- Async side effects do not compromise core state consistency.
- The system remains deployable as one application during early and middle stages.

### Negative

- Initial development requires more discipline.
- More classes may be needed for commands, handlers, ports, adapters, value objects, and policies.
- Developers must avoid using Symfony convenience features in the wrong layers.
- Some simple CRUD use cases may feel heavier than in classic Symfony MVC.

### Neutral / Operational

- The application is deployed as one backend service by default.
- Queue workers may run as separate processes but are part of the same application codebase.
- PostgreSQL remains the canonical data store.
- Redis, RabbitMQ, and OpenSearch are operational dependencies but not canonical state owners.

## Trade-offs

- We accept more code structure in exchange for clearer domain boundaries.
- We accept a modular monolith in exchange for simpler deployment and faster domain discovery.
- We accept eventual consistency for search, notifications, and reporting in exchange for keeping core state changes reliable.
- We accept that not every use case needs full CQRS, but commands and queries should be separated when it improves clarity.
- We accept that Symfony components are useful implementation tools, but they must not dictate the domain model.

## Impact on Layers

### Domain

- Owns business rules, invariants, and domain behavior.
- May contain aggregates, entities, value objects, domain services, policies, specifications, and domain events.
- Must not depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, or HTTP.
- Must not expose infrastructure concerns as part of the business model.

### Application

- Owns use case orchestration.
- May contain commands, command handlers, queries, query handlers, application services, ports, and use-case DTOs.
- Coordinates permission checks, loading aggregates, calling domain behavior, persistence, transaction boundaries, and event publication.
- Must not own core business invariants that belong to aggregates or domain services.

### Infrastructure

- Implements adapters for persistence, messaging, cache, search, file storage, and external APIs.
- May use Doctrine, DBAL, Messenger, Redis clients, OpenSearch clients, and Symfony service wiring.
- Depends inward on ports and domain/application contracts.
- Must not become the place where hidden business behavior is implemented.

### Interfaces / UI

- Contains delivery adapters such as HTTP controllers, CLI commands, webhook controllers, request DTOs, response presenters, and admin adapters.
- Controllers must stay thin.
- Controllers translate transport input into application commands/queries and return responses.
- Controllers must not enforce workflow rules, mutate aggregates directly, or contain permission decisions.

## Impact on DDD

This decision makes DDD the main modeling approach for business behavior.

Bounded contexts will be introduced when there is a clear business responsibility and meaningful boundary.

Initial bounded context candidates include:

- Identity & Access;
- Projects;
- Issues;
- Workflow;
- Boards;
- Collaboration;
- Notifications;
- Search;
- Reporting;
- Audit.

Aggregates should protect invariants.

Important aggregate candidates include:

- Project;
- Issue;
- Workflow;
- Board;
- Team.

Value objects should represent meaningful concepts such as identifiers, status, priority, issue type, project key, workflow transition, and permission scope.

Domain services and policies should be introduced only when behavior does not naturally belong to one aggregate.

Repositories should represent collection-like access to aggregates, not generic data access helpers.

## Impact on EDA

Event-Driven Architecture is allowed for facts that already happened and for asynchronous side effects.

Domain events may include:

- IssueCreated;
- IssueAssigned;
- IssueStatusChanged;
- IssueCommented;
- ProjectArchived;
- WorkflowTransitionChanged.

Async side effects may include:

- notifications;
- search indexing;
- reporting projections;
- audit projections when acceptable;
- external integrations;
- attachment processing.

Events must not replace commands.

Async messages must not be required for core state changes to be valid.

Async handlers must be idempotent.

If reliable publication becomes necessary, the Outbox Pattern must be considered and documented in a separate ADR.

## Impact on CQRS

CQRS is optional but encouraged where it makes intent and read/write responsibilities clearer.

Commands should represent user intent, for example:

- CreateProject;
- CreateIssue;
- AssignIssue;
- ChangeIssueStatus;
- CommentOnIssue;
- MoveIssueOnBoard.

Queries should represent read use cases, for example:

- GetIssueDetails;
- SearchIssues;
- GetProjectBoard;
- GetUserAssignedIssues;
- GetOverdueIssues;
- GetAuditTrail.

Command handlers should not return complex read models.

Query handlers may use optimized DBAL, projections, or OpenSearch where appropriate.

Critical user-visible state must be confirmable from the database.

## Symfony Implementation Direction

Symfony will be used as the backend framework and integration layer.

Allowed Symfony mechanisms include:

- Messenger for command/query dispatch and async messages;
- Doctrine and DBAL for persistence adapters;
- Security Voters or Access Decision Manager for authorization;
- Validator for input and DTO validation;
- Event Dispatcher for framework-level events;
- Console Commands for workers, imports, reindexing, and maintenance;
- Cache Contracts for cache adapters;
- Lock Component for justified concurrency control;
- Workflow Component only if it fits the domain model.

Symfony attributes and framework-specific concerns should stay outside the Domain layer.

Doctrine ORM mapping should be treated as persistence detail.

Doctrine listeners must not hide important business mutations.

Symfony Workflow Component must not force an anemic domain model.

## Risks

- Developers may create decorative DDD folders without real domain modeling.
- Developers may over-engineer simple features with unnecessary abstractions.
- Symfony convenience patterns may leak into Domain.
- Doctrine entities may become anemic data bags or overloaded infrastructure/domain hybrids.
- Async messaging may be misused for core state changes.
- Module boundaries may be chosen too early or too broadly.
- OpenSearch or Redis may accidentally become sources of truth.

## Mitigations

- Start structure from concrete use cases, not from all possible modules.
- Use `ChangeIssueStatus` as the first deep architecture validation scenario.
- Keep controllers thin and review them for business logic leakage.
- Keep Domain free from Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, and HTTP.
- Document important module and transaction decisions as ADRs.
- Add tests for domain invariants, application handlers, permissions, and async idempotency.
- Treat Redis and OpenSearch as derived or temporary stores only.
- Keep RabbitMQ for side effects, not core state mutation.

## Migration / Adoption Plan

1. Keep the current Symfony skeleton as technical bootstrap.
2. Use this ADR as the baseline for module structure discussion.
3. Decide initial `src/` structure before creating business modules.
4. Design the first deep scenario: `ChangeIssueStatus`.
5. Create only the modules and layers needed by the first real use cases.
6. Add additional ADRs for module boundaries, workflow architecture, authorization model, Outbox usage, search consistency, and cache strategy when needed.

## Open Questions

- Should bounded contexts be represented directly under `src/Boardly/` or under `src/`?
- Should each bounded context contain `Domain`, `Application`, `Infrastructure`, and `Interfaces` folders from the start?
- Should shared kernel concepts live under `Shared` or be avoided until duplication proves a need?
- Should Symfony Messenger be used for synchronous command/query dispatch, or should handlers be wired explicitly first?
- Should Doctrine ORM mappings be attributes, XML, or PHP configuration for domain entities?
- Should Workflow be a separate bounded context or part of Issues initially?

## References

- Related ADRs: none
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
