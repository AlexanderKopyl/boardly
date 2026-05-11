# Boardly Codex Skills

Project skills are reusable execution playbooks for Boardly subagents.

## Rule

Agents define roles. Skills define repeatable workflows.

To avoid duplicated behavior:

- keep product/architecture procedures inside `.codex/skills/*/SKILL.md`;
- keep subagent files focused on role, scope, and skill selection;
- when a request matches a skill, the subagent should follow the skill workflow instead of reinventing the response structure.

## Task lifecycle skills

| Skill | Use for |
| --- | --- |
| `task-planning` | create `<task-folder>/planning.md` with scoped checkbox implementation plan |
| `task-analysis` | create `<task-folder>/analysis.md` with architecture/security/test analysis |
| `task-implementation` | execute checkbox plan task-by-task, select subagents per task, update checklist |

## Backend / platform skills

| Skill | Use for |
| --- | --- |
| `domain-modeling` | aggregates, invariants, value objects, domain events, transaction boundaries |
| `feature-architecture` | end-to-end backend architecture for a feature/use case |
| `workflow-design` | issue statuses, transitions, guards, validators, Symfony Workflow fit |
| `async-flow` | RabbitMQ/Messenger, Outbox, retries, DLQ, idempotency |
| `search-indexing` | OpenSearch/Elasticsearch documents, projections, reindexing |
| `permission-modeling` | RBAC/ABAC, project membership, issue visibility, voters/policies |
| `testing-strategy` | domain/application/integration/async/security/frontend test design |
| `cache-performance` | Redis, TTL, invalidation, hot paths, DB query risks |
| `observability-operations` | health checks, queues, logs, metrics, recovery commands |
| `adr-writing` | architecture decision records and trade-off documentation |

## Frontend skills

| Skill | Use for |
| --- | --- |
| `frontend-context-architecture` | ADR-0006 context-based frontend hexagonal architecture, layers, imports |
| `frontend-use-case-flow` | frontend application use cases, ports, orchestration, browser-side flows |
| `frontend-api-integration` | HTTP gateways, API contracts, error normalization, mapping |
| `frontend-auth-session` | memory-only access token, HttpOnly refresh cookie, bootstrap, logout, protected routes |
| `frontend-ui-composition` | Next.js pages, layouts, providers, React components, hooks, guards, forms |
| `frontend-review-checklist` | ADR-0006 frontend review, auth safety, API/UI boundary checks |

## Output modifier skills

| Skill | Use for |
| --- | --- |
| `caveman-response` | short, direct, low-token answers when explicitly requested |
| `graphify-knowledge-map` | compact knowledge graphs, dependency maps, architecture maps |

## Task lifecycle rules

- Planning saves an artifact to the specified task folder.
- Analysis saves an artifact to the specified task folder.
- Implementation creates or updates a checklist from the plan.
- Implementation must use checkbox syntax.
- Implementation must run task-by-task, not as one untracked bulk change.
- Implementation must select relevant subagent(s) per checkbox task.
- Implementation marks `- [x]` only after implementation and verification/documentation.

## MemPalace rules

Use MemPalace only if remembered context can materially change the decision:

- previous DEV ticket context;
- architectural decisions;
- why previous approaches were selected/rejected;
- known risks/regressions;
- cross-feature context;
- business logic already discussed;
- repeated integration nuances.

Do not use MemPalace for simple repo discovery such as class location, route/controller lookup, payload builder lookup, `dataLayer.push` lookup, or file lists connected to a flow.

## Global Boardly rules

- Business behavior first, framework mechanics second.
- Modular monolith by default.
- No microservices unless there is a strong operational/product reason.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ is for asynchronous side effects, not core consistency.
- Next.js is the frontend framework, not the source of business truth.
- Backend remains source of truth for business invariants, permissions, workflow transitions, identity lifecycle, persistence, and project/task state.
- Frontend context domain models are not backend aggregates.
- Frontend access token must be memory-only.
- Frontend refresh token must be HttpOnly and unreadable by JavaScript.
- Commands represent user intentions.
- Queries do not mutate state.
- Domain events represent business facts.
- Integration events are stable external contracts.
- Async handlers must be idempotent.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions and auditability must be designed early.
- Compact output must not hide important security, permission, source-of-truth, or transaction-boundary risks.
- Knowledge graphs must preserve direction and relationship labels.
