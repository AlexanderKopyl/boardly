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

Read accepted ADRs before making architecture, structure, or module-boundary decisions.

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
| Workflow design | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| CQRS command/query design | `docs/architecture/project-architecture-rules.md` and `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md` |
| RabbitMQ / async flow | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| Redis cache design | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| OpenSearch / Elasticsearch design | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| Important architecture decision | Existing accepted ADRs, then `docs/adr/0000-template.md` |
| Writing an ADR | Existing accepted ADRs, then `docs/adr/0000-template.md` |

## Before Changing Code

Before creating or modifying code, check:

1. Does the task require reading the architecture rules?
2. Does the task require reading accepted ADRs?
3. Does the task require choosing a specialized subagent?
4. Does the task require a new ADR?
5. Is the change consistent with the existing documentation?
6. Is the requested change code, documentation, or architecture design?

If documentation and user request conflict, explain the conflict before changing code.

## Source Structure Rules for Agents

For source code placement, follow:

```text
docs/adr/0002-use-boardly-context-based-source-structure.md
```

Default classification:

```text
src/Boardly             -> product-specific Boardly code
src/Boardly/SharedKernel -> small shared Boardly domain concepts
src/Shared              -> technical abstractions that do not know about Boardly
```

Do not create future modules just because they are listed as candidates.

Create modules only when a real use case requires them.

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

Details and constraints are documented in:

```text
docs/architecture/project-architecture-rules.md
```

Subagent routing for this scenario is documented in:

```text
docs/agents/subagents-map.md
```

When implementing or designing this scenario, also read:

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
```
