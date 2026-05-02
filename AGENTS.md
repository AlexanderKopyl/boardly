# AGENTS.md

## Purpose

This file is the entrypoint for AI agents working in Boardly.

Do not duplicate architecture rules here. Use this file only as a navigation map and operating guide.

Boardly is a Jira-like project/task/workflow management system. It is not a CRM.

## How to Use This Repository

Before proposing architecture, generating code, or changing structure, read the relevant documentation below.

If a rule is needed, reference the source document instead of restating it in `AGENTS.md`.

Accepted ADRs are binding project decisions. If an ADR conflicts with a generic recommendation, follow the accepted ADR unless the user explicitly asks to replace it with a new ADR.

## Documentation Map

### Architecture Rules

Read:

```text
docs/architecture/project-architecture-rules.md
```

Use this document when working with:

- Hexagonal Architecture;
- DDD;
- EDA;
- CQRS;
- Symfony layer boundaries;
- Redis usage;
- RabbitMQ usage;
- OpenSearch / Elasticsearch usage;
- transaction boundaries;
- source-of-truth decisions;
- module and bounded context design.

If the task touches architecture, this is the primary source.

### Accepted ADRs

Read accepted ADRs before making architecture, structure, module-boundary, event-delivery, or infrastructure decisions.

#### ADR-0001: Modular Monolith, Hexagonal Architecture, and DDD

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
```

Use this ADR when working with:

- baseline backend architecture;
- Modular Monolith decisions;
- Hexagonal Architecture layer direction;
- DDD boundaries;
- CQRS usage;
- EDA usage;
- source-of-truth decisions;
- avoiding premature microservices.

#### ADR-0002: Boardly Context-Based Source Structure

```text
docs/adr/0002-use-boardly-context-based-source-structure.md
```

Use this ADR when working with:

- `src/` structure;
- `src/Boardly` product-specific code;
- `src/Boardly/SharedKernel` shared Boardly domain concepts;
- `src/Shared` technical abstractions;
- bounded context folders;
- deciding where new classes should live;
- avoiding empty/decorative modules.

#### ADR-0003: Transactional Outbox for Domain Event Delivery

```text
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

Use this ADR when working with:

- domain event delivery;
- outbox persistence;
- Messenger/RabbitMQ publication;
- async side effects;
- search indexing events;
- audit/activity projections;
- notification events;
- reporting projections;
- event retry and idempotency;
- avoiding direct message dispatch from domain services, entities, or command handlers as part of business mutation.

### Design Documents

#### ChangeIssueStatus

```text
docs/design/change-issue-status.md
```

Use this document when working with:

- the `ChangeIssueStatus` use case;
- Issue status ownership;
- Workflow transition validation boundary;
- permission check boundary;
- transaction boundary for status changes;
- `IssueStatusChanged` domain event shape;
- async side effects after status change;
- failure cases for issue status changes.

### Subagents Map

Read:

```text
docs/agents/subagents-map.md
```

Use this document when deciding which specialized subagent should handle the task.

It defines routing for:

- Product & Domain Analyst;
- DDD / Domain Modeling;
- Workflow & Permissions;
- Symfony Architecture;
- Async / Messaging;
- Search & Read Models;
- Cache & Performance;
- Testing & QA;
- Security;
- DevOps / Observability;
- ADR Writer.

If a task crosses several responsibilities, use the multi-subagent routing rules from this document.

### ADR Template

Read:

```text
docs/adr/0000-template.md
```

Use this template when creating Architecture Decision Records.

Create ADRs for decisions about:

- module boundaries;
- aggregate ownership;
- workflow architecture;
- authorization model;
- transaction boundaries;
- event contracts;
- Outbox usage;
- search consistency;
- cache strategy;
- major Symfony component usage;
- major infrastructure choices.

Accepted ADR naming format:

```text
docs/adr/0001-short-decision-title.md
```

## Agent Routing Guide

Use the documentation like this:

| Task type | Read first |
|---|---|
| Choosing a specialized subagent | `docs/agents/subagents-map.md` |
| Architecture proposal | `docs/architecture/project-architecture-rules.md`, then accepted ADRs |
| Symfony structure proposal | `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`, `docs/adr/0002-use-boardly-context-based-source-structure.md` |
| Source code placement decision | `docs/adr/0002-use-boardly-context-based-source-structure.md` |
| Creating a new Boardly module/context | `docs/adr/0002-use-boardly-context-based-source-structure.md`, then `docs/architecture/project-architecture-rules.md` |
| Domain modeling | `docs/architecture/project-architecture-rules.md`, `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`, and `docs/agents/subagents-map.md` |
| Workflow design | `docs/architecture/project-architecture-rules.md`, `docs/design/change-issue-status.md`, and `docs/agents/subagents-map.md` |
| CQRS command/query design | `docs/architecture/project-architecture-rules.md` and `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md` |
| RabbitMQ / async flow | `docs/architecture/project-architecture-rules.md`, `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, and `docs/agents/subagents-map.md` |
| Domain event delivery / Outbox | `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, then `docs/architecture/project-architecture-rules.md` |
| Redis cache design | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| OpenSearch / Elasticsearch design | `docs/architecture/project-architecture-rules.md`, `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, and `docs/agents/subagents-map.md` |
| ChangeIssueStatus design or implementation | `docs/design/change-issue-status.md`, then ADR-0001, ADR-0002, and ADR-0003 |
| Important architecture decision | Existing accepted ADRs, then `docs/adr/0000-template.md` |
| Writing an ADR | Existing accepted ADRs, then `docs/adr/0000-template.md` |

## Before Changing Code

Before creating or modifying code, check:

1. Does the task require reading the architecture rules?
2. Does the task require reading accepted ADRs?
3. Does the task require reading a scenario design document?
4. Does the task require choosing a specialized subagent?
5. Does the task require a new ADR?
6. Is the change consistent with the existing documentation?
7. Is the requested change code, documentation, or architecture design?

If documentation and user request conflict, explain the conflict before changing code.

## Source Structure Rules for Agents

For source code placement, follow:

```text
docs/adr/0002-use-boardly-context-based-source-structure.md
```

Default classification:

```text
src/Boardly              -> product-specific Boardly code
src/Boardly/SharedKernel -> small shared Boardly domain concepts
src/Shared               -> technical abstractions that do not know about Boardly
```

Do not create future modules just because they are listed as candidates.

Create modules only when a real use case requires them.

## Event Delivery Rules for Agents

For reliable domain event delivery, follow:

```text
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

Default rule:

```text
Business state change + outbox event record must be committed in the same DB transaction.
```

Do not dispatch Messenger/RabbitMQ messages directly from:

- domain entities;
- domain services;
- command handlers as part of the business mutation.

Use the outbox for durable domain event delivery when async side effects must reliably follow committed business state.

Async consumers must be idempotent.

## Default Architecture Response Format

When proposing architecture, use the response format defined in:

```text
docs/architecture/project-architecture-rules.md
```

Do not redefine that format here.

## First Deep Design Scenario

For initial architecture validation, use:

```text
ChangeIssueStatus
```

The scenario design is documented in:

```text
docs/design/change-issue-status.md
```

Details and constraints are also documented in:

```text
docs/architecture/project-architecture-rules.md
```

Subagent routing for this scenario is documented in:

```text
docs/agents/subagents-map.md
```

When implementing or changing this scenario, read:

```text
docs/design/change-issue-status.md
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```
