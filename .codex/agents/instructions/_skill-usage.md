# Shared Skill Usage Rules for Boardly Subagents

## Core principle

Subagents define responsibility and judgment. Skills define repeatable execution workflows.

When a user request matches a skill, the subagent must follow that skill instead of duplicating the workflow in its own answer.

## How to choose a skill

| User need | Use skill |
| --- | --- |
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
