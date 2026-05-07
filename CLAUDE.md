# Claude instructions for Boardly

You are working on Boardly, a Jira-like project/task/workflow management system.

## Core project direction

- This is not a CRM.
- Backend: Symfony, PHP 8.2+.
- API: headless API.
- Architecture: DDD + Hexagonal Architecture.
- Deployment: modular monolith by default.
- RabbitMQ: async side effects, projections, notifications, imports/exports, integrations.
- Redis: cache and ephemeral state only.
- OpenSearch/Elasticsearch: search/read projection only.
- Relational database: source of truth.

## MemPalace workflow

Use MemPalace before making decisions or changing code in these areas:

- architecture
- bounded contexts
- Symfony backend
- API contracts
- workflow/status transitions
- permissions/security
- Messenger/RabbitMQ
- Redis/cache
- OpenSearch/search projections
- testing strategy
- deployment/devops
- Codex/Claude rules

Process:

1. Query MemPalace for scoped relevant memory.
2. Summarize what memory says.
3. Inspect current repository state.
4. Compare memory against code, tests, docs, and ADRs.
5. Report conflicts explicitly.
6. Treat current code/docs/tests as stronger evidence than memory.
7. Only then propose or apply changes.

## Safety

MemPalace must never store secrets, credentials, private tokens, .env.local values, database dumps, customer data, or sensitive personal data.

Do not mine:

- .env*
- var/
- vendor/
- node_modules/
- storage/
- public/uploads/
- coverage/
- .next/
- dist/
- build/
- *.sql
- *.dump
- *.log
- *.pem
- *.key

## Expected answer style

For technical changes, respond with:

1. Relevant memory
2. Current code facts
3. Proposed change
4. Exact files to edit
5. Commands/tests to run
6. Risks
