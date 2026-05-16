# Gemini instructions for Boardly

You are working on Boardly, a Jira-like project/task/workflow management system.

This file is the default Gemini context. Keep it small. Do not preload large rule files unless the task requires them.

## Project baseline

- Boardly is not a CRM.
- Backend: Symfony, PHP 8.2+.
- Architecture: DDD + Hexagonal Architecture.
- Default deployment shape: modular monolith.
- API style: headless/API-first backend.
- RabbitMQ/Messenger: asynchronous side effects, projections, notifications, imports/exports, integrations.
- Redis: cache and ephemeral/fast storage only.
- OpenSearch/Elasticsearch: search/read-side projections only.
- Relational database: source of truth.
- Frontend: Next.js + TypeScript with context-based frontend hexagonal architecture.

## Core workflow

Use this order by default:

1. Understand the task contract: goal, non-goals, constraints, and done criteria.
2. Inspect only the minimum task-relevant repository files.
3. Use search-first and diff-first discovery before opening full files.
4. Plan before editing.
5. Implement narrowly.
6. Verify with the smallest relevant command first.
7. Summarize changed files, rationale, verification, risks, and rollback notes.

Do not mentally load the whole repository. Load extra rules only by topic.

## When to use MemPalace

Use MemPalace only when remembered context can materially change the decision.

Use it for:

- previous ticket context;
- architectural decisions;
- why a previous approach was selected or rejected;
- known risks and regressions;
- cross-feature context;
- business logic already discussed;
- repeated integration nuances;
- historical constraints not obvious from repository files.

Do not use MemPalace for simple repository discovery:

- where a class is located;
- which route calls a controller;
- where a payload is built;
- what changed in the current Git diff;
- what file names exist in the repository.

For repository discovery, inspect the repository directly with `git diff`, `git grep`, `rg`, file tree, and targeted file reads.

MemPalace is context only. It is not the source of truth. If memory conflicts with current code, tests, docs, or ADRs, current repository evidence wins.

## Rule lookup map

Load only the relevant file for the task.

| Task area | Read when relevant |
|---|---|
| General agent behavior / security | `AGENTS.md` |
| Codex permissions / registered skills / subagents | `.codex/config.toml` |
| Claude-specific behavior | `CLAUDE.md` only when changing Claude rules |
| Agent/subagent routing | `docs/agents/subagents-map.md` |
| Generic DDD rules | `docs/architecture/generic/ddd-rules.md` |
| Hexagonal architecture | `docs/architecture/generic/hexagonal-architecture-rules.md` |
| Symfony backend rules | `docs/architecture/generic/symfony-backend-rules.md` |
| CQRS | `docs/architecture/generic/cqrs-rules.md` |
| Event-driven architecture | `docs/architecture/generic/eda-rules.md` |
| Transactions | `docs/architecture/generic/transaction-rules.md` |
| DB/cache/search source of truth | `docs/architecture/generic/infrastructure-source-of-truth-rules.md` |
| Cache/search | `docs/architecture/generic/cache-and-search-rules.md` |
| SOLID/design principles | `docs/architecture/generic/solid-design-principles.md` |
| ADR writing | `docs/architecture/generic/adr-rules.md` |
| Legacy migration | `docs/architecture/generic/legacy-migration-rules.md` |
| Project decisions | `docs/adr/` |
| Development setup | `docs/development/` |
| Existing playbooks | `skills/<topic>/SKILL.md` |

## Existing skill system

Do not create Gemini-specific skills by default.

The project already has reusable skills under `skills/`. Use them as playbooks when relevant, but do not load all of them at once.

Common mappings:

| Need | Skill |
|---|---|
| Repository onboarding/setup review | `skills/repo-onboarding/SKILL.md` |
| Task analysis | `skills/task-analysis/SKILL.md` |
| Task planning | `skills/task-planning/SKILL.md` |
| Task implementation | `skills/task-implementation/SKILL.md` |
| Verification evidence | `skills/verification-evidence/SKILL.md` |
| Context compaction | `skills/context-compaction/SKILL.md` |
| Domain model design | `skills/domain-modeling/SKILL.md` |
| Feature architecture | `skills/feature-architecture/SKILL.md` |
| Workflow/status logic | `skills/workflow-design/SKILL.md` |
| Async/Messenger/RabbitMQ | `skills/async-flow/SKILL.md` |
| Search/OpenSearch | `skills/search-indexing/SKILL.md` |
| Permissions/security modeling | `skills/permission-modeling/SKILL.md` |
| Tests | `skills/testing-strategy/SKILL.md` |
| Cache/performance | `skills/cache-performance/SKILL.md` |
| Observability/operations | `skills/observability-operations/SKILL.md` |
| ADRs | `skills/adr-writing/SKILL.md` |

## Architecture baseline

Target backend structure:

```text
src/<Module>/Domain/
src/<Module>/Application/
src/<Module>/Infrastructure/
src/<Module>/Interfaces/
```

Layer meaning:

- `Domain`: business behavior, invariants, value objects, domain events.
- `Application`: use cases, commands, queries, handlers, ports, transactions.
- `Infrastructure`: Doctrine, DBAL, external APIs, queues, cache, search, framework adapters.
- `Interfaces`: HTTP controllers, CLI commands, request DTOs, response DTOs, webhooks.

Symfony is an implementation framework, not the architecture center.

Do not treat current legacy folders as target architecture.

Do not put business behavior into:

```text
src/Controller
src/Entity
src/Repository
src/Service
Doctrine listeners
Symfony Forms
HTTP controllers
```

Controllers stay thin. Doctrine is a persistence adapter, not the business owner.

## Classification language

When reviewing legacy or current structure, classify findings as:

- `TARGET_RULE`
- `CURRENT_STATE`
- `LEGACY_EXCEPTION`
- `WRONG_AND_MUST_BE_FIXED`

Current state is evidence, not permission.

## Safety

Never read, print, summarize, modify, or infer secrets from:

```text
.env*
**/.env*
auth.json
**/auth.json
.npmrc
**/.npmrc
.pypirc
**/.pypirc
config/secrets/**
var/private/**
var/backups/**
var/dumps/**
storage/private/**
storage/backups/**
storage/dumps/**
frontend/.env*
frontend/**/.env*
*.sql
**/*.sql
*.sql.gz
**/*.sql.gz
*.dump
**/*.dump
*.bak
**/*.bak
*.sqlite
**/*.sqlite
*.sqlite3
**/*.sqlite3
*.db
**/*.db
*.pem
**/*.pem
*.key
**/*.key
*.p12
**/*.p12
*.pfx
**/*.pfx
id_rsa
**/id_rsa
id_ed25519
**/id_ed25519
```

Ask before destructive actions, dependency changes, migrations, deployment changes, network access, or changes to secrets/local machine configuration.

## Response format for technical work

Use:

```md
## Current facts
## Proposed change
## Files to edit
## Verification commands
## Risks
## Rollback
```
