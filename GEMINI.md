# Gemini instructions for Boardly

You are working on Boardly, a Jira-like project/task/workflow management system.

This file is the Gemini CLI entrypoint. Do not duplicate the full project rules here. Load and follow the existing canonical project guidance.

## Required project context

Read these files first:

```text
AGENTS.md
CLAUDE.md
.codex/config.toml
docs/agents/subagents-map.md
```

If a task touches architecture, modules, domain logic, API contracts, permissions, workflows, Messenger, Redis, OpenSearch, testing, operations, or AI-agent rules, also inspect the relevant docs under:

```text
docs/architecture/
docs/adr/
docs/development/
skills/
```

## Existing skill system

Do not create new Gemini-specific skills by default.

The project already has reusable skills under:

```text
skills/
```

Use existing skills as execution playbooks. Important skills include:

```text
skills/repo-onboarding/SKILL.md
skills/task-analysis/SKILL.md
skills/task-planning/SKILL.md
skills/task-implementation/SKILL.md
skills/verification-evidence/SKILL.md
skills/context-compaction/SKILL.md
skills/domain-modeling/SKILL.md
skills/feature-architecture/SKILL.md
skills/workflow-design/SKILL.md
skills/async-flow/SKILL.md
skills/search-indexing/SKILL.md
skills/permission-modeling/SKILL.md
skills/testing-strategy/SKILL.md
skills/cache-performance/SKILL.md
skills/observability-operations/SKILL.md
skills/adr-writing/SKILL.md
```

If Gemini cannot auto-load these skills, read the relevant `SKILL.md` files manually before planning or implementation.

## Core operating model

Use this order:

1. Load durable project guidance.
2. Understand task goal, non-goals, constraints, and done criteria.
3. Inspect the current repository state.
4. Prefer narrow search and targeted file reads.
5. Plan before editing.
6. Implement narrowly.
7. Verify with the smallest relevant command first.
8. Summarize changed files, rationale, verification, risks, and rollback notes.

Do not give the whole repository to the model mentally. Use durable guidance, narrow retrieval, and resumable task artifacts.

## Architecture baseline

Boardly backend target architecture:

```text
src/<Module>/Domain/
src/<Module>/Application/
src/<Module>/Infrastructure/
src/<Module>/Interfaces/
```

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

Controllers must stay thin.

Doctrine is a persistence adapter.
RabbitMQ/Messenger handles asynchronous side effects.
Redis is cache/fast storage only.
OpenSearch/Elasticsearch is search/read projection only.
Relational database is the source of truth.

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

## Final answer format for technical work

Use:

```md
## Current facts
## Proposed change
## Files to edit
## Verification commands
## Risks
## Rollback
```
