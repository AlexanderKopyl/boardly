# CLAUDE.md

## Purpose

This file is the Claude Code entrypoint for Boardly.

Do not duplicate architecture rules, ADR content, or design documents here.

Use this file only as a routing map for:

- which project document to read for the current task;
- which Claude command to use;
- which subagent to delegate to;
- which skill/playbook should guide the answer;
- when to verify external documentation.

Boardly is a Jira-like project/task/workflow management system. It is not a CRM.

## Token Rule

Do not read all documentation upfront.

Read only the smallest set of project documents required by the current task.

If the task is simple and does not touch architecture, security, persistence, async, search, cache, or existing scenarios, inspect the relevant files only.

If more context is needed, explain why before loading additional documents.

Use `caveman-response` when the user asks for short answers or when the task is simple.

Use `graphify-context` when a compact relationship map is better than long prose.

Do not claim fixed token savings. Token reduction depends on task size and context.

## Operating Rule

Before proposing architecture, generating code, or changing structure:

1. Classify the task.
2. Select the relevant document from the routing tables below.
3. Read only that document and directly required dependencies.
4. Use the relevant Claude command, subagent, and skill.
5. Inspect existing repository structure before naming files/classes.
6. Explain conflicts if the user request contradicts project docs.

Accepted ADRs are binding only when they are relevant to the task.

If a rule already exists in project docs, reference that source instead of restating it here.

## Project Documentation Routing

### Architecture Rules

| Read | Only when the task touches |
| --- | --- |
| `docs/architecture/project-architecture-rules.md` | architecture proposal, DDD, Hexagonal Architecture, EDA, CQRS, Symfony boundaries, Redis, RabbitMQ, OpenSearch/Elasticsearch, transaction boundaries, source-of-truth, module or bounded-context design |

### ADRs

| Read | Only when the task touches |
| --- | --- |
| `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md` | baseline backend architecture, modular monolith, Hexagonal Architecture, DDD, CQRS, EDA, avoiding premature microservices |
| `docs/adr/0002-use-boardly-context-based-source-structure.md` | `src/` structure, context placement, module folders, SharedKernel, Shared technical abstractions |
| `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md` | domain events, Outbox, Messenger/RabbitMQ publication, async side effects, indexing/projections after business changes |
| `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` | API-first direction, Symfony/Next.js boundary, controllers, API DTOs, read models, frontend/backend responsibility |
| `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` | authentication, JWT access tokens, refresh cookies, token rotation, CORS/CSRF, IdentityAccess, account lifecycle |
| `docs/adr/0000-template.md` | creating or updating an ADR |

### Design Documents

| Read | Only when the task touches |
| --- | --- |
| `docs/design/change-issue-status.md` | `ChangeIssueStatus`, issue status changes, workflow transition validation, permission boundary, status-change events |
| `docs/design/authentication-api-strategy.md` | register/login/refresh/logout/me API, account approval routes, auth errors, rate limiting, frontend auth behavior |
| `docs/design/account-domain-model.md` | `Account`, account lifecycle, RefreshSession, refresh-token families, account repository, IdentityAccess domain events |

### Subagents Map

| Read | Only when the task touches |
| --- | --- |
| `docs/agents/subagents-map.md` | choosing between several specialized agents or routing a task across multiple agents |

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

Commands live in `.claude/commands/`.

## Claude Subagents Map

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

Subagents live in `.claude/agents/`.

## Claude Skills Map

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
| Short/token-efficient answers | `caveman-response` |
| Compact relationship map | `graphify-context` |

Skills live in `.claude/skills/`.

## Task Routing Guide

| Situation | Read only if relevant | Use |
| --- | --- | --- |
| Architecture proposal | architecture rules; matching ADR only | `/analyze-feature`, `symfony-architecture` |
| Source placement | ADR-0002 | `/implementation-plan`, `symfony-architecture` |
| Domain model | architecture rules; ADR-0001 if structure/architecture matters | `/design-domain`, `ddd-modeling` |
| Issue status change | change-issue-status design; ADR-0003 if events are involved | `/design-workflow`, `workflow-permissions` |
| Auth/account work | auth design; account model; ADR-0005 | `/design-domain`, `testing-security-reviewer` |
| API boundary | ADR-0004 | `/implementation-plan`, `symfony-architecture` |
| Domain event delivery | ADR-0003 | `/design-async-flow`, `async-messaging` |
| Search/read models | ADR-0003 if event-driven indexing is involved | `/design-search-indexing`, `search-read-models` |
| Redis/cache | architecture rules only if cache affects source-of-truth or consistency | `/analyze-feature`, `cache-performance` |
| Security review | only the relevant ADR/design doc | `/review-security`, `testing-security-reviewer` |
| Testing review | only the relevant feature/design doc | `/review-tests`, `testing-security-reviewer` |
| New ADR | relevant existing ADRs; template | `/write-adr`, `devops-adr-documentation` |
| Short/simple response | no extra docs unless needed | `caveman-response` |
| Compact context map | only directly related docs/nodes | `graphify-context` |

## External Documentation Checks

Use Context7 or official docs only when syntax, framework behavior, or package details may be version-sensitive.

Check current docs for the specific component/package being changed, not for the whole stack.

Examples:

- Symfony Workflow only when workflow configuration/API is involved.
- Symfony Messenger only when messages, handlers, transports, retries, or workers are involved.
- Doctrine docs only when ORM/DBAL/migrations/transactions/query behavior is involved.
- Redis docs only when Redis-specific behavior is involved.
- OpenSearch/Elasticsearch docs only when indexing/search behavior is involved.
- Claude Code docs only when settings, commands, subagents, skills, or MCP syntax is involved.

Do not use external docs to override accepted Boardly ADRs unless the user asks for a new decision.

## MCP Usage

Project MCP config lives in `.mcp.json`.

Use:

- `context7` for current documentation lookup;
- `sequential-thinking` for complex multi-step architecture, workflow, permission, and trade-off analysis.

Do not add secrets or local-only credentials to MCP config.

## Safety Boundaries

Ask before destructive file commands, dependency changes, migrations, database-destructive operations, Docker/deploy/CI changes, network access, or touching secrets/local env files.

Do not read or expose `.env`, credentials, private keys, dumps, backups, auth files, or local machine secrets.

## Response Rule

For project work, prefer this structure:

1. Short answer
2. Sources selected, not all sources
3. Command, subagent, and skill used
4. Decision or plan
5. Risks / conflicts
6. Next action
