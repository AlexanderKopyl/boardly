# Agent instructions for Boardly

## Project context

Boardly is a Jira-like project/task/workflow management system, not a CRM.

Backend:

- Symfony
- PHP 8.2+
- DDD + Hexagonal Architecture
- Modular monolith by default
- Headless/API-first backend
- RabbitMQ for asynchronous side effects
- Redis for cache/fast storage only
- OpenSearch/Elasticsearch for search/read-side projections only
- Relational database is the source of truth

Frontend:

- Next.js + TypeScript
- Context-based frontend hexagonal architecture according to ADR-0006
- Backend remains source of truth for business invariants, permissions, workflow transitions, identity lifecycle, persistence, and project/task state

## Core operating model

Use this order by default:

1. Load durable project guidance: `AGENTS.md`, relevant ADRs, `.codex/config.toml`, relevant skills.
2. Understand the task contract: goal, non-goals, constraints, and done criteria.
3. Prefer diff-first and search-first discovery before opening full files.
4. Build a minimal context pack when scope/files are unclear.
5. Read only the minimum task-relevant files.
6. Plan before editing.
7. Implement narrowly.
8. Verify with the smallest relevant command first, then broader checks if needed.
9. Summarize changed files, rationale, verification, and remaining risks.

Do not give the whole repository to the model mentally. Use durable guidance, narrow retrieval, context packs, and resumable task artifacts.

## Context and tool-output discipline

Treat context as a scarce engineering resource.

Rules:

- Prefer `git diff --name-only`, `git diff --stat`, `git grep`, `rg`, symbol search, and targeted file reads before opening full files.
- Prefer diff-only and section-only reads for large files.
- Do not paste full logs, full generated files, dependency installs, or broad command output into context.
- Preserve exact error lines only when they affect the next decision.
- Use `head`, `tail`, grep filters, line ranges, and summaries for large outputs.
- Save durable findings into task artifacts instead of carrying raw tool output through the session.
- Use `<task-folder>/context-pack.md` when the relevant file set is unclear.
- Use `<task-folder>/context-budget.md` when context is large or likely wasteful.
- Use `<task-folder>/compaction.md` when the session becomes long/noisy or needs handoff.

Do not reduce context by hiding security, permission, source-of-truth, CQRS bus, frontend auth/session, ADR-0006, ADR-0007, or transaction-boundary risks.

## Task lifecycle

For managed task work, use task artifacts:

```text
<task-folder>/onboarding.md
<task-folder>/context-pack.md
<task-folder>/context-budget.md
<task-folder>/planning.md
<task-folder>/analysis.md
<task-folder>/checklist.md
<task-folder>/implementation.md
<task-folder>/verification.md
<task-folder>/agent-metrics.md
<task-folder>/compaction.md
```

Planning and analysis must save artifacts when a task folder is specified.

Implementation must update the checklist task-by-task:

```markdown
- [ ] Task not done
- [x] Task done
```

Implementation must not happen as one untracked bulk change. Select relevant subagents per checkbox task and mark a task done only after implementation and verification/documentation.

## MemPalace usage

Use MemPalace only when remembered context can materially change the decision.

Use MemPalace for:

- previous DEV ticket context;
- architectural decisions;
- why a previous approach was selected or rejected;
- known risks and regressions;
- cross-feature context;
- business logic already discussed;
- repeated integration nuances;
- historical constraints that are not obvious from the current repository files.

Good query examples:

```text
GA4 purchase Measurement Protocol checkout confirm
Apple Pay merchant validation LiqPay
product of day validation date range
ESputnik discount order product
Boardly frontend ADR-0006 identity access auth session
Boardly ChangeIssueStatus workflow permission audit
```

Do not use MemPalace for simple repository discovery:

- where a class is located;
- which route calls a controller;
- where a payload is built;
- where `dataLayer.push` is called;
- which files are connected to a flow;
- what changed in the current Git diff;
- what file names exist in the repository.

For repository discovery, inspect the repository directly with `git diff`, `git grep`, `rg`, file tree, and targeted file reads.

MemPalace is context only. It is not the source of truth. If memory conflicts with current code, tests, docs, or ADRs, treat current repository evidence as stronger and report the conflict.

## Security

Never read, print, summarize, modify, or infer secrets from:

```text
.env*
**/.env*
auth.json
**/auth.json
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

## Architecture rules

- Business behavior first, framework mechanics second.
- Symfony is implementation detail, not the domain model.
- Controllers stay thin.
- HTTP controllers must dispatch Commands through `CommandBusInterface` and Queries through `QueryBusInterface`.
- HTTP controllers must never inject or invoke concrete Application handlers directly.
- Concrete command handlers must be registered on `command.bus`; concrete query handlers must be registered on `query.bus`.
- Direct controller calls like `($this->createProjectHandler)(...)`, `$this->createProjectHandler->__invoke(...)`, or `$this->createProjectHandler->handle(...)` are architecture violations.
- Domain logic belongs in the domain/application layers, not controllers, forms, Doctrine listeners, or generic services.
- Doctrine is persistence adapter, not the owner of business behavior.
- RabbitMQ/Messenger handles asynchronous side effects, not core consistency.
- Redis is not source of truth.
- OpenSearch/Elasticsearch is not source of truth.
- Frontend context domain models are not backend aggregates.
- Frontend must not authoritatively decide permissions or workflow transitions.
- Access token must be memory-only on frontend.
- Refresh token must be HttpOnly and unreadable by JavaScript.

## Before changing files

Provide or record:

1. Current repository facts.
2. Relevant ADRs/docs loaded.
3. Relevant context pack and context-budget decisions if used.
4. Relevant MemPalace memory only if used.
5. Conflicts between memory and code/docs if any.
6. Proposed implementation plan.
7. Verification commands.
8. Risks and rollback/follow-up notes.
