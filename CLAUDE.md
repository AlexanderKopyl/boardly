# CLAUDE.md

## Purpose

This file is the Claude Code entrypoint for Boardly.

Do not duplicate architecture rules, ADR content, or design documents here.

Use this file only as a navigation map for:

- which project documents to read;
- which Claude command to use;
- which subagent to delegate to;
- which skill/playbook should guide the answer;
- when to verify external documentation.

Boardly is a Jira-like project/task/workflow management system. It is not a CRM.

## Operating Rule

Before proposing architecture, generating code, or changing structure:

1. Read the relevant project documentation.
2. Use accepted ADRs as binding decisions.
3. Use the relevant Claude command, subagent, and skill.
4. Inspect existing repository structure before naming files/classes.
5. Explain conflicts if the user request contradicts project docs.

If a rule already exists in project docs, reference that source instead of restating it here.

## Project Documentation Map

### Primary Architecture Rules

Read first for architecture work:

```text
docs/architecture/project-architecture-rules.md
```

Use for Hexagonal Architecture, DDD, EDA, CQRS, Symfony boundaries, Redis, RabbitMQ, OpenSearch/Elasticsearch, transactions, source-of-truth, module design, and bounded contexts.

### Accepted ADRs

Read accepted ADRs before structural or infrastructure decisions:

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
```

Use ADRs as the source of truth for accepted architecture decisions.

### ADR Template

Use when creating a new ADR:

```text
docs/adr/0000-template.md
```

### Design Documents

Read scenario design documents before implementing related behavior:

```text
docs/design/change-issue-status.md
docs/design/authentication-api-strategy.md
docs/design/account-domain-model.md
```

Use these for issue status changes, authentication API behavior, IdentityAccess, account lifecycle, and refresh-session design.

### Subagents Map

Read before choosing specialized agents:

```text
docs/agents/subagents-map.md
```

## Claude Commands Map

| Task | Command |
| --- | --- |
| Analyze feature scope and behavior | `/analyze-feature` |
| Design aggregate/domain model | `/design-domain` |
| Design issue workflow/status transitions | `/design-workflow` |
| Design roles, permissions, visibility | `/design-permissions` |
| Design Messenger/RabbitMQ async flow | `/design-async-flow` |
| Design search/read model/indexing | `/design-search-indexing` |
| Review security/authorization risk | `/review-security` |
| Review test coverage | `/review-tests` |
| Write architecture decision record | `/write-adr` |
| Produce implementation plan | `/implementation-plan` |

Commands live in:

```text
.claude/commands/
```

## Claude Subagents Map

Use subagents for specialized reasoning. Do not overload the main context with every concern.

| Task | Subagent |
| --- | --- |
| Product behavior, MVP scope, acceptance criteria | `product-domain-analyst` |
| Symfony placement, services, framework integration | `symfony-architecture` |
| Aggregates, invariants, value objects, events | `ddd-modeling` |
| Workflow, transitions, guards, permissions | `workflow-permissions` |
| Messenger, RabbitMQ, Outbox, retries, DLQ | `async-messaging` |
| OpenSearch/Elasticsearch, projections, search | `search-read-models` |
| Redis, cache, TTL, invalidation, hot paths | `cache-performance` |
| Security, permissions, tests, regression risks | `testing-security-reviewer` |
| Observability, recovery, ADR documentation | `devops-adr-documentation` |

Subagents live in:

```text
.claude/agents/
```

## Claude Skills Map

Use skills as repeatable playbooks. Agents decide; skills structure the work.

| Concern | Skill |
| --- | --- |
| Feature architecture | `feature-architecture` |
| Domain modeling | `domain-modeling` |
| Workflow design | `workflow-design` |
| Permission modeling | `permission-modeling` |
| Async flow | `async-flow` |
| Search indexing | `search-indexing` |
| Cache/performance | `cache-performance` |
| Testing strategy | `testing-strategy` |
| Observability/operations | `observability-operations` |
| ADR writing | `adr-writing` |

Skills live in:

```text
.claude/skills/
```

## Routing Guide

| Situation | Read first | Use |
| --- | --- | --- |
| Architecture proposal | architecture rules + accepted ADRs | `/analyze-feature`, `symfony-architecture` |
| Source placement | ADR-0002 | `/implementation-plan`, `symfony-architecture` |
| Domain model | architecture rules + ADR-0001 | `/design-domain`, `ddd-modeling` |
| Issue status change | change-issue-status design + ADR-0003 | `/design-workflow`, `workflow-permissions` |
| Auth/account work | auth design + account model + ADR-0005 | `/design-domain`, `testing-security-reviewer` |
| API boundary | ADR-0004 | `/implementation-plan`, `symfony-architecture` |
| Domain event delivery | ADR-0003 | `/design-async-flow`, `async-messaging` |
| Search/read models | architecture rules + ADR-0003 | `/design-search-indexing`, `search-read-models` |
| Redis/cache | architecture rules | `/analyze-feature`, `cache-performance` |
| Security review | relevant ADR/design doc + subagents map | `/review-security`, `testing-security-reviewer` |
| Testing review | relevant design doc | `/review-tests`, `testing-security-reviewer` |
| New ADR | existing ADRs + template | `/write-adr`, `devops-adr-documentation` |

## External Documentation Checks

Use Context7 or official docs when syntax, framework behavior, or package details may be version-sensitive.

Check official/current docs for:

- Symfony components: Service Container, Routing, Security, Workflow, Messenger, Serializer, Validator, Cache, Console, Testing;
- Doctrine ORM/DBAL/Migrations;
- RabbitMQ and Symfony Messenger transports;
- Redis and Symfony Cache Redis integration;
- OpenSearch or Elasticsearch client/indexing behavior;
- PHPUnit, PHPStan, Rector, PHP-CS-Fixer, Composer;
- Claude Code settings, commands, subagents, skills, and MCP.

Do not use external docs to override accepted Boardly ADRs unless the user asks for a new decision.

## MCP Usage

Project MCP config lives in:

```text
.mcp.json
```

Use:

- `context7` for current documentation lookup;
- `sequential-thinking` for complex multi-step architecture, workflow, permission, and trade-off analysis.

Do not add secrets or local-only credentials to MCP config.

## Safety Boundaries

Ask before:

- destructive file commands;
- dependency installation/removal/update;
- migrations or database destructive operations;
- Docker/deploy/CI changes;
- network access;
- reading or touching secrets/local env files.

Do not read or expose `.env`, credentials, private keys, dumps, backups, auth files, or local machine secrets.

## Response Rule

For project work, prefer this structure:

1. Short answer
2. Sources read / should be read
3. Command, subagent, and skill used
4. Decision or plan
5. Risks / conflicts
6. Next action
