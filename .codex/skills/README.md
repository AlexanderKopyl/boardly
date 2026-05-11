# Boardly Codex Skills

Project skills are reusable execution playbooks for Boardly subagents.

## Rule

Agents define roles. Skills define repeatable workflows.

To avoid duplicated behavior:

- keep product/architecture procedures inside `.codex/skills/*/SKILL.md`;
- keep subagent files focused on role, scope, and skill selection;
- when a request matches a skill, the subagent should follow the skill workflow instead of reinventing the response structure.

## Required guidance loading

Backend planning, analysis, implementation, and review must consider:

```text
AGENTS.md
docs/development/backend/boardly-symfony-developer-rules.md
relevant docs/adr/*
relevant docs/architecture/*
```

Frontend planning, analysis, implementation, and review must consider:

```text
AGENTS.md
docs/development/frontend/boardly-frontend-developer-rules.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md when auth/session is involved
docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md
docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md when UI/styling/shared primitives are involved
docs/development/backend/boardly-symfony-developer-rules.md when API/auth/backend contract behavior matters
```

Skills do not replace accepted ADRs or developer rulebooks.

## Task lifecycle skills

| Skill | Use for |
| --- | --- |
| `repo-onboarding` | deterministic narrow discovery, candidate files, durable guidance, diff/search-first reads |
| `task-planning` | create `<task-folder>/planning.md` with scoped checkbox implementation plan |
| `task-analysis` | create `<task-folder>/analysis.md` with architecture/security/test analysis |
| `task-implementation` | execute checkbox plan task-by-task, select subagents per task, update checklist |
| `verification-evidence` | record exact commands, actual results, not-run reasons, final verification status |
| `context-compaction` | create resumable memo for long/noisy sessions and handoffs |

## Frontend lifecycle skills

| Skill | Use for |
| --- | --- |
| `frontend-task-planning` | create `<task-folder>/frontend-planning.md` using ADR-0004/0005/0006/0007 and frontend rules |
| `frontend-task-analysis` | create `<task-folder>/frontend-analysis.md` with context/API/auth/UI/style/test risks |
| `frontend-task-implementation` | execute frontend checklist task-by-task and update frontend artifacts |

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
| `frontend-style-system` | ADR-0007 Tailwind, CSS variables, shadcn-style primitives, Radix, tokens, variants |
| `frontend-review-checklist` | ADR-0006/0007 frontend review, auth safety, API/UI/style boundary checks |

## Output modifier skills

| Skill | Use for |
| --- | --- |
| `caveman-response` | short, direct, low-token answers when explicitly requested |
| `graphify-knowledge-map` | compact knowledge graphs, dependency maps, architecture maps |

## Task lifecycle rules

Use the full lifecycle for managed backend/mixed task folders:

```text
repo-onboarding -> task-planning -> task-analysis -> task-implementation -> verification-evidence
```

Use the frontend lifecycle for managed frontend task folders:

```text
repo-onboarding -> frontend-task-planning -> frontend-task-analysis -> frontend-task-implementation -> verification-evidence
```

Add `context-compaction` when the session is long, noisy, or needs handoff/resume.

Backend/mixed artifacts:

```text
<task-folder>/onboarding.md
<task-folder>/planning.md
<task-folder>/analysis.md
<task-folder>/checklist.md
<task-folder>/implementation.md
<task-folder>/verification.md
<task-folder>/compaction.md
```

Frontend artifacts:

```text
<task-folder>/onboarding.md
<task-folder>/frontend-planning.md
<task-folder>/frontend-analysis.md
<task-folder>/frontend-checklist.md
<task-folder>/frontend-implementation.md
<task-folder>/frontend-verification.md
<task-folder>/compaction.md
```

Implementation rules:

- Planning saves an artifact to the specified task folder.
- Analysis saves an artifact to the specified task folder.
- Implementation creates or updates a checklist from the plan.
- Implementation must use checkbox syntax.
- Implementation must run task-by-task, not as one untracked bulk change.
- Implementation must select relevant subagent(s) per checkbox task.
- Implementation must record verification evidence before marking a task done.
- Implementation marks `- [x]` only after implementation and verification/documentation.
- `task-verifier` should audit completion before closing important tasks.

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
- Tailwind CSS is the default frontend styling system.
- CSS variables own theme-level semantic design tokens.
- Shared UI primitives must remain generic and context-free.
- shadcn-style components are project-owned code.
- Radix should be used for accessibility-sensitive primitives when needed.
- Commands represent user intentions.
- Queries do not mutate state.
- Domain events represent business facts.
- Integration events are stable external contracts.
- Async handlers must be idempotent.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions and auditability must be designed early.
- Compact output must not hide important security, permission, source-of-truth, frontend-auth, frontend-style, or transaction-boundary risks.
- Knowledge graphs must preserve direction and relationship labels.
