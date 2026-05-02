# AGENTS.md

## Purpose

This file is the entrypoint for AI agents working in Boardly.

Do not duplicate architecture rules here. Use this file only as a navigation map and operating guide.

Boardly is a Jira-like project/task/workflow management system. It is not a CRM.

## How to Use This Repository

Before proposing architecture, generating code, or changing structure, read the relevant documentation below.

If a rule is needed, reference the source document instead of restating it in `AGENTS.md`.

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
| Architecture proposal | `docs/architecture/project-architecture-rules.md` |
| Symfony structure proposal | `docs/architecture/project-architecture-rules.md` |
| Domain modeling | `docs/architecture/project-architecture-rules.md` |
| Workflow design | `docs/architecture/project-architecture-rules.md` |
| CQRS command/query design | `docs/architecture/project-architecture-rules.md` |
| RabbitMQ / async flow | `docs/architecture/project-architecture-rules.md` |
| Redis cache design | `docs/architecture/project-architecture-rules.md` |
| OpenSearch / Elasticsearch design | `docs/architecture/project-architecture-rules.md` |
| Important architecture decision | `docs/adr/0000-template.md` |
| Writing an ADR | `docs/adr/0000-template.md` |

## Before Changing Code

Before creating or modifying code, check:

1. Does the task require reading the architecture rules?
2. Does the task require an ADR?
3. Is the change consistent with the existing documentation?
4. Is the requested change code, documentation, or architecture design?

If documentation and user request conflict, explain the conflict before changing code.

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
