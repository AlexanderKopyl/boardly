# Shared Skill Usage Rules for Boardly Subagents

## Core principle

Subagents define responsibility and judgment. Skills define repeatable execution workflows.

When a user request matches a skill, the subagent must follow that skill instead of duplicating the workflow in its own answer.

Also follow `.codex/agents/instructions/_mempalace-usage.md` before using MemPalace.

## How to choose a skill

| User need | Use skill |
| --- | --- |
| Create a durable task plan in a specified task folder | `task-planning` |
| Analyze a task and save analysis artifact into a task folder | `task-analysis` |
| Implement a checkbox plan task-by-task and update checklist | `task-implementation` |
| Aggregate, invariant, value object, domain event, repository, transaction boundary | `domain-modeling` |
| End-to-end backend architecture for a feature/use case | `feature-architecture` |
| Statuses, transitions, guards, validators, workflow rules | `workflow-design` |
| RabbitMQ, Messenger, Outbox, retries, DLQ, idempotency | `async-flow` |
| OpenSearch/Elasticsearch, indexing, read models, projections | `search-indexing` |
| Roles, permissions, voters, policies, membership, visibility | `permission-modeling` |
| Test matrix, test scenarios, quality gates, regression risks | `testing-strategy` |
| Redis, TTL, invalidation, hot paths, query optimization, locks | `cache-performance` |
| Health checks, logs, metrics, workers, recovery, production readiness | `observability-operations` |
| Architecture decision records, alternatives, consequences, trade-offs | `adr-writing` |
| Short answer, low-token response, terse summary, compact final output | `caveman-response` |
| Knowledge graph, dependency map, architecture graph, relation map | `graphify-knowledge-map` |
| Frontend context/layer boundaries, ADR-0006 structure, imports | `frontend-context-architecture` |
| Frontend application use cases, ports, browser-side orchestration | `frontend-use-case-flow` |
| Frontend HTTP gateways, API contracts, error normalization, mapping | `frontend-api-integration` |
| Frontend auth, memory-only access token, HttpOnly refresh cookie, protected routes | `frontend-auth-session` |
| Next.js pages, providers, React components, hooks, forms, guards | `frontend-ui-composition` |
| Frontend ADR-0006 review, auth safety, UI/API boundary review | `frontend-review-checklist` |

## Task lifecycle skills

Use these when the user works through a task folder:

1. `task-planning` creates `<task-folder>/planning.md`.
2. `task-analysis` creates `<task-folder>/analysis.md`.
3. `task-implementation` creates or updates `<task-folder>/checklist.md` and appends `<task-folder>/implementation.md`.

Implementation must be task-by-task:

- use checkbox syntax;
- select relevant subagent(s) for each checkbox task;
- implement one task at a time;
- mark `- [x]` only after implementation and verification/documentation;
- do not implement the whole plan in one untracked pass.

## Multi-skill requests

Use multiple skills when the request crosses boundaries.

Examples:

- `ChangeIssueStatus` usually needs `feature-architecture`, `domain-modeling`, `workflow-design`, `permission-modeling`, `async-flow`, `search-indexing`, `testing-strategy`, and possibly `adr-writing`.
- `SearchIssues` usually needs `feature-architecture`, `search-indexing`, `permission-modeling`, `cache-performance`, and `testing-strategy`.
- `GetProjectBoard` usually needs `feature-architecture`, `search-indexing` or read-model reasoning, `permission-modeling`, `cache-performance`, and `observability-operations`.
- Frontend IdentityAccess usually needs `frontend-context-architecture`, `frontend-auth-session`, `frontend-use-case-flow`, `frontend-api-integration`, `frontend-ui-composition`, and `testing-strategy`.
- Frontend review usually needs `frontend-review-checklist`, `frontend-context-architecture`, `frontend-auth-session`, `frontend-api-integration`, and `frontend-ui-composition`.
- A compact answer to a previously analyzed feature can add `caveman-response` as the final output layer.
- A compact structural map can add `graphify-knowledge-map` after the relevant architecture/domain/frontend skill.

## Priority rule

If this is a managed task folder workflow, start with lifecycle skills:

1. `task-planning`
2. `task-analysis`
3. `task-implementation`

If several backend skills apply, start with the business architecture skill, then add specialized skills:

1. `feature-architecture`
2. `domain-modeling`
3. `workflow-design` or `permission-modeling`
4. `async-flow`, `search-indexing`, or `cache-performance`
5. `testing-strategy`
6. `observability-operations`
7. `adr-writing`

If several frontend skills apply, start with ADR-0006 boundaries, then add specialized skills:

1. `frontend-context-architecture`
2. `frontend-use-case-flow`
3. `frontend-auth-session` or `frontend-api-integration`
4. `frontend-ui-composition`
5. `frontend-review-checklist`
6. `testing-strategy`

Final output modifiers:

- Use `graphify-knowledge-map` when the user asks for graph output.
- Use `caveman-response` only as a final compression layer when the user asks for short output.

## MemPalace quick rule

Use MemPalace only when memory can materially change the decision:

- previous DEV ticket context;
- architectural decisions;
- previously selected/rejected approaches;
- known risks/regressions;
- cross-feature context;
- previously discussed business logic;
- repeated integration nuances.

Do not use MemPalace for simple repo discovery such as class locations, routes, payload builders, `dataLayer.push`, or flow-related file lists.

## Global constraints

- Do not assume existing folders, entities, controllers, services, queues, indexes, database tables, frontend contexts, or frontend app structure unless explicitly provided.
- Boardly starts as a modular monolith.
- Symfony is an implementation framework, not the architectural center.
- Next.js is the frontend framework, not the source of business truth.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ is for asynchronous side effects, not core consistency.
- Backend remains source of truth for business invariants, permissions, workflow transitions, identity lifecycle, persistence, and project/task state.
- Frontend context domain models are not backend aggregates.
- Frontend access token must be memory-only.
- Frontend refresh token must be HttpOnly and unreadable by JavaScript.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions, visibility, and auditability must be designed early.
- Compact output must not remove security, permission, audit, source-of-truth, or transaction-boundary warnings when they matter.
- Graph output must use directed edges and meaningful relationship labels.
- Do not claim guaranteed token reduction ratios for compact or graph output.
