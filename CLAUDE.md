# CLAUDE.md

Boardly project instructions for Claude Code.

## Project summary

Boardly is a Jira-like project, task, board, and workflow management system. It is not a CRM.

Target stack:
- Backend: Symfony 7, PHP 8.3+
- Architecture: Modular Monolith, DDD + Hexagonal Architecture
- DB: relational database as source of truth
- Async: RabbitMQ via Symfony Messenger
- Cache / fast storage: Redis
- Search / read-side: OpenSearch or Elasticsearch
- Frontend direction: API-first Symfony backend with Next.js frontend

## Core architecture rules

- Symfony is the delivery/framework layer, not the domain model.
- Controllers stay thin.
- Domain owns business rules and invariants.
- Application coordinates commands, queries, handlers, transactions, and ports.
- Infrastructure implements Doctrine, Messenger, Redis, OpenSearch, filesystem, and external adapters.
- DB is source of truth; Redis, OpenSearch, and RabbitMQ are supporting infrastructure.
- Do not introduce microservices by default.
- Avoid Manager, Helper, BaseService, and CommonService without a concrete business reason.
- Do not invent files, classes, modules, routes, entities, or tables without inspecting the repo first.

## Before coding

1. Inspect repository structure.
2. Identify whether the project is empty, partial, or already structured.
3. Split affected code by Domain, Application, Infrastructure, and Interfaces/UI.
4. Identify permission/security impact.
5. Identify tests to add or run.
6. Explain the minimal plan.
7. Ask before destructive commands, dependency changes, migrations, Docker/deploy changes, secrets, or network access.

## Layer map

| Concern | Place |
| --- | --- |
| Business rules, invariants, domain events | Domain |
| Use cases, commands, queries, transactions, ports | Application |
| Doctrine, Messenger, Redis, OpenSearch, filesystem, APIs | Infrastructure |
| HTTP controllers, API DTOs, request/response mapping | Interfaces/UI |
| CLI commands | Interfaces/UI or Infrastructure |
| Pure business-rule tests | Domain tests |
| Handler/use-case tests | Application tests |
| Adapter/persistence tests | Infrastructure/integration tests |

## What to use when

| Situation | Command | Agent | Skill |
| --- | --- | --- | --- |
| New feature / unclear product behavior | /analyze-feature | product-domain-analyst | feature-architecture |
| Domain model / aggregate / invariant | /design-domain | ddd-modeling | domain-modeling |
| Statuses / transitions / guards | /design-workflow | workflow-permissions | workflow-design |
| Roles / membership / visibility / voters | /design-permissions | workflow-permissions | permission-modeling |
| Messenger / RabbitMQ / Outbox / retries | /design-async-flow | async-messaging | async-flow |
| OpenSearch / projections / read models | /design-search-indexing | search-read-models | search-indexing |
| Redis / TTL / invalidation / hot paths | /analyze-feature | cache-performance | cache-performance |
| Security review | /review-security | testing-security-reviewer | permission-modeling |
| Test coverage review | /review-tests | testing-security-reviewer | testing-strategy |
| ADR / trade-off documentation | /write-adr | devops-adr-documentation | adr-writing |
| Implementation steps | /implementation-plan | symfony-architecture | feature-architecture |

## Commands

/analyze-feature, /design-domain, /design-workflow, /design-permissions, /design-async-flow, /design-search-indexing, /review-security, /review-tests, /write-adr, /implementation-plan.

## Agents

- product-domain-analyst: product behavior, MVP scope, actors, acceptance criteria.
- symfony-architecture: Symfony implementation direction while preserving boundaries.
- ddd-modeling: aggregates, entities, value objects, invariants, domain events.
- workflow-permissions: issue workflow, transitions, guards, roles, visibility.
- async-messaging: Messenger/RabbitMQ, Outbox, retries, DLQ, idempotency.
- search-read-models: search documents, projections, stale index behavior.
- cache-performance: Redis usage, TTL, invalidation, hot paths, query risks.
- testing-security-reviewer: permission, workflow, audit, and regression review.
- devops-adr-documentation: operations, recovery, observability, ADRs.

## Skills

feature-architecture, domain-modeling, workflow-design, permission-modeling, async-flow, search-indexing, cache-performance, testing-strategy, observability-operations, adr-writing.

## Documentation to check

Check official/current docs before relying on framework behavior, config syntax, or integration details.

- Symfony: Service Container, Autowiring, Autoconfiguration, Routing, HttpKernel, Security, Voters, Workflow, Messenger, Serializer, Validator, Forms, Cache, Console, Testing.
- Doctrine: ORM, DBAL, Migrations, transactions, locking, associations, query performance.
- Infrastructure: RabbitMQ, Symfony Messenger transports, Redis, Symfony Cache Redis adapter, OpenSearch or Elasticsearch, Docker Compose.
- Quality: PHPUnit, PHPStan, Rector, PHP-CS-Fixer, Composer scripts.
- Claude Code: settings, slash commands, subagents, skills, MCP configuration.

## Security and permissions

- Authorization must be server-side and explicit.
- Always check actor, action, resource, and context.
- Project membership and issue visibility are core security concerns.
- Search/read models must not leak hidden issues.
- Audit-sensitive mutations must be logged.
- Permission cache is allowed only with TTL and safe invalidation.
- Do not read or expose secrets, credentials, private keys, dumps, or local env files.

## Source-of-truth rules

- DB owns persistent project, issue, workflow, permission, and audit state.
- Redis may cache derived data, counters, sessions, locks, and rate limits.
- OpenSearch/Elasticsearch may denormalize searchable/read-side data.
- RabbitMQ may process side effects after core state is committed.
- If DB and search/cache disagree, DB wins.

## Async rules

- Core state changes must happen synchronously in a DB transaction.
- Use Messenger/RabbitMQ for notifications, indexing, projections, integrations, and other side effects.
- Use Outbox when reliable publishing matters.
- Consumers must be idempotent.
- Failed messages must be observable and recoverable.

## Testing expectations

- Domain rules need fast unit tests.
- Application handlers need orchestration and transaction tests.
- Infrastructure adapters need integration tests when behavior matters.
- Permissions and visibility need explicit tests.
- Workflow transitions need allowed/forbidden transition tests.
- Async handlers need duplicate, retry, and failure tests.
- Search/projections need consistency and reindex tests.
- Audit behavior needs tests for sensitive mutations.

## MVP focus

Start with users, teams, project roles, projects, issues/tasks, workflow statuses, assignee, reporter, priority, due date, comments, mentions, attachments, labels/tags, Kanban board, notifications, audit log, search, and basic reports.

Postpone unless requested: Scrum, advanced sprints, automation builder, custom fields engine, plugin marketplace, complex workflow designer, BI, time tracking, AI prioritization, and cross-organization multitenancy.

## Default response shape

Design: short answer, explanation, proposed architecture, layer responsibilities, Symfony direction, permissions, async/search/cache, tests, risks, next step, common mistakes.

Implementation: implementation plan, files to inspect, files to change, step-by-step changes, tests, verification, risks/TODOs.

Review: summary, critical issues, architecture issues, security/permission issues, testing gaps, performance risks, recommended fixes, acceptable as-is.
