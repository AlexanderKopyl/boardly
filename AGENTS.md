# Agent instructions for Boardly

## Project context

This project is a Jira-like project/task/workflow management system, not a CRM.

Backend: Symfony, PHP 8.2+.
Architecture: DDD + Hexagonal Architecture.
Deployment style: modular monolith by default.
API style: headless API.
Messaging: RabbitMQ.
Cache/ephemeral state: Redis.
Search: OpenSearch/Elasticsearch.
Relational database is the source of truth.

## MemPalace usage

Before architecture, backend, security, testing, API, workflow, search, Redis, RabbitMQ, or Codex-related work:

1. Query MemPalace for relevant project memory.
2. Summarize relevant memory found.
3. Inspect current repository files.
4. Compare memory with code, tests, docs, and ADRs.
5. If memory conflicts with current code, treat current code/docs/tests as stronger evidence and report the conflict.
6. Do not modify files until the plan is clear.

Use MemPalace as context only. It is not the source of truth.

## Required pre-work memory queries

Use scoped memory queries such as:

- architecture decisions
- backend Symfony rules
- bounded context rules
- workflow context
- permissions and security rules
- testing strategy
- Codex rules
- known risks

Do not load unrelated memory.

## Safety

Never store or retrieve secrets through MemPalace.

Do not mine or rely on:

- .env*
- var/
- vendor/
- node_modules/
- storage/
- public/uploads/
- coverage/
- build artifacts
- database dumps
- logs with sensitive data

## Before changing files

Always provide:

1. Relevant MemPalace memory found.
2. Current code facts.
3. Conflicts between memory and code.
4. Proposed implementation plan.
5. Tests or commands to verify the change.
