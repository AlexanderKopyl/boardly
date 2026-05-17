# Shared Skill Usage Rules for Boardly Subagents

## Core principle

Subagents define responsibility and judgment. Skills define repeatable execution workflows.

When a user request matches a skill, the subagent must follow that skill instead of duplicating the workflow in its own answer.

Also follow `.codex/agents/instructions/_mempalace-usage.md` before using MemPalace.

## Required guidance loading

Before planning, analysis, or implementation, load the guidance that can affect the task.

Backend task guidance:

- `AGENTS.md`
- `docs/development/backend/boardly-symfony-developer-rules.md`
- relevant ADRs from `docs/adr/`
- relevant architecture rules from `docs/architecture/`

Frontend task guidance:

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` when auth/session is involved
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` when UI/styling/shared primitives are involved
- `docs/development/backend/boardly-symfony-developer-rules.md` when API/auth/backend contract behavior matters

Do not treat skills as replacements for accepted ADRs or developer rulebooks.

## Context economy rules

Treat context as a scarce engineering resource.

Use `context-pack-builder` when the relevant files/docs are unclear.

Use `context-budget-audit` when the task is large, many docs/skills/artifacts are candidates, or context feels bloated.

Use `context-compaction` when the session becomes long/noisy or needs handoff.

Use `agent-evaluation-metrics` after meaningful planning/analysis/implementation/review work when the workflow quality should be measured.

Tool-output hygiene:

- prefer `git diff --name-only`, `git diff --stat`, `git grep`, `rg`, symbol search, and targeted file reads;
- prefer diff-only and section-only reads for large files;
- do not paste full logs, full generated files, dependency installs, or broad command output;
- preserve exact error lines only when they affect the next decision;
- use `head`, `tail`, grep filters, line ranges, and summaries for large outputs;
- save durable findings into task artifacts instead of carrying raw tool output through the session.

Do not reduce context by hiding security, permission, source-of-truth, CQRS bus, frontend auth/session, ADR-0006, ADR-0007, or transaction-boundary risks.

## How to choose a skill

| User need | Use skill |
| --- | --- |
| New session, unknown area, branch review discovery, candidate files | `repo-onboarding` |
| Build a minimal must-read/maybe-read/do-not-read task context pack | `context-pack-builder` |
| Estimate/control context pressure and decide full vs section/diff/summarize/skip | `context-budget-audit` |
| Create a durable task plan in a specified task folder | `task-planning` |
| Analyze a task and save analysis artifact into a task folder | `task-analysis` |
| Implement a checkbox plan task-by-task and update checklist | `task-implementation` |
| Frontend-specific planning with ADR-0004/0005/0006/0007 and frontend rules | `frontend-task-planning` |
| Frontend-specific analysis with ADR/style/API/auth/test impact | `frontend-task-analysis` |
| Frontend-specific checkbox implementation with ADR/rules verification | `frontend-task-implementation` |
| Record exact verification commands, results, not-run reasons, final status | `verification-evidence` |
| Compact long-running session state into resumable memo | `context-compaction` |
| Record workflow metrics: files opened/edited, tools, verification, assumptions, unsupported claims | `agent-evaluation-metrics` |
| Aggregate, invariant, value object, domain event, repository, transaction boundary | `domain-modeling` |
| End-to-end backend architecture for a feature/use case | `feature-architecture` |
| HTTP controllers, Commands, Queries, concrete handlers, bus registration, controller boundary review | `symfony-cqrs-bus-boundary` |
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
| Frontend Tailwind/CSS variables/shadcn/Radix/shared primitives | `frontend-style-system` |
| Frontend ADR-0006/0007 review, auth safety, UI/API boundary review | `frontend-review-checklist` |

## Hard backend controller rule

Whenever creating, modifying, or reviewing HTTP controllers, Commands, Queries, handlers, or bus config, use `symfony-cqrs-bus-boundary`.

Hard rule:

- HTTP controllers must never inject or invoke concrete Application handlers directly.
- Controllers must dispatch Commands through `CommandBusInterface`.
- Controllers must dispatch Queries through `QueryBusInterface`.
- Concrete command handlers must be registered on `command.bus`.
- Concrete query handlers must be registered on `query.bus`.
- Direct controller calls like `($this->createProjectHandler)(...)`, `$this->createProjectHandler->__invoke(...)`, or `$this->createProjectHandler->handle(...)` are architecture violations.

## Task lifecycle skills

Use these when the user works through a task folder:

0. `repo-onboarding` creates `<task-folder>/onboarding.md` when scope/files are unclear.
1. `context-pack-builder` creates `<task-folder>/context-pack.md` when a minimal context pack is needed.
2. `context-budget-audit` creates `<task-folder>/context-budget.md` when context pressure should be controlled.
3. `task-planning` creates `<task-folder>/planning.md`.
4. `task-analysis` creates `<task-folder>/analysis.md`.
5. `task-implementation` creates or updates `<task-folder>/checklist.md` and appends `<task-folder>/implementation.md`.
6. `verification-evidence` creates or updates `<task-folder>/verification.md`.
7. `agent-evaluation-metrics` creates `<task-folder>/agent-metrics.md` when workflow measurement is useful.
8. `context-compaction` creates or updates `<task-folder>/compaction.md` when the session becomes long/noisy or needs handoff.

Frontend-specific lifecycle:

0. `repo-onboarding` creates `<task-folder>/onboarding.md` when scope/files are unclear.
1. `context-pack-builder` creates `<task-folder>/context-pack.md` when needed.
2. `context-budget-audit` creates `<task-folder>/context-budget.md` when needed.
3. `frontend-task-planning` creates `<task-folder>/frontend-planning.md`.
4. `frontend-task-analysis` creates `<task-folder>/frontend-analysis.md`.
5. `frontend-task-implementation` creates or updates `<task-folder>/frontend-checklist.md`, `<task-folder>/frontend-implementation.md`, and `<task-folder>/frontend-verification.md`.
6. `verification-evidence` may also update `<task-folder>/verification.md` for cross-stack evidence.
7. `agent-evaluation-metrics` creates `<task-folder>/agent-metrics.md` when workflow measurement is useful.
8. `context-compaction` creates or updates `<task-folder>/compaction.md` when needed.

Implementation must be task-by-task:

- use checkbox syntax;
- select relevant subagent(s) for each checkbox task;
- implement one task at a time;
- record verification evidence before marking a task done;
- mark `- [x]` only after implementation and verification/documentation;
- do not implement the whole plan in one untracked pass.

## Verification gate

Use `task-verifier` or `verification-evidence` when the user asks whether work is done, when implementation claims completion, or before closing a task.

Checklist items are not done unless all are true:

1. Code/docs/config change exists or item is explicitly non-code.
2. Changed files match the task scope.
3. Verification command or manual check is recorded.
4. Failures and not-run checks are disclosed.
5. Architecture/security/source-of-truth/frontend-auth/frontend-style risks are addressed or documented.

## Priority rule

If this is a managed backend/mixed task folder workflow, use the full lifecycle:

1. `repo-onboarding` when scope/files are unclear
2. `context-pack-builder` when the exact context set is unclear
3. `context-budget-audit` when context is large or many docs/artifacts are candidates
4. `task-planning`
5. `task-analysis`
6. `task-implementation`
7. `verification-evidence`
8. `agent-evaluation-metrics` when measurement is useful
9. `context-compaction` when needed

If this is a managed frontend task folder workflow, use the frontend lifecycle:

1. `repo-onboarding` when scope/files are unclear
2. `context-pack-builder` when the exact context set is unclear
3. `context-budget-audit` when context is large or many docs/artifacts are candidates
4. `frontend-task-planning`
5. `frontend-task-analysis`
6. `frontend-task-implementation`
7. `verification-evidence` or `task-verifier`
8. `agent-evaluation-metrics` when measurement is useful
9. `context-compaction` when needed

If several backend skills apply, start with the business architecture skill, then add specialized skills:

1. `feature-architecture`
2. `domain-modeling`
3. `symfony-cqrs-bus-boundary` for HTTP controllers / commands / queries / handlers / bus config
4. `workflow-design` or `permission-modeling`
5. `async-flow`, `search-indexing`, or `cache-performance`
6. `testing-strategy`
7. `observability-operations`
8. `adr-writing`

If several frontend skills apply, start with ADR-0006 boundaries, then add specialized skills:

1. `frontend-context-architecture`
2. `frontend-use-case-flow`
3. `frontend-auth-session` or `frontend-api-integration`
4. `frontend-ui-composition`
5. `frontend-style-system` when UI/styling/shared primitives are involved
6. `frontend-review-checklist`
7. `testing-strategy`

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
- HTTP controllers must dispatch Commands through `CommandBusInterface` and Queries through `QueryBusInterface`.
- HTTP controllers must never inject or invoke concrete Application handlers directly.
- Concrete handlers must be registered on the correct command/query bus.
- Next.js is the frontend framework, not the source of business truth.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ is for asynchronous side effects, not core consistency.
- Backend remains source of truth for business invariants, permissions, workflow transitions, identity lifecycle, persistence, and project/task state.
- Frontend context domain models are not backend aggregates.
- Frontend access token must be memory-only.
- Frontend refresh token must be HttpOnly and unreadable by JavaScript.
- Tailwind CSS is the default frontend styling system.
- CSS variables own theme-level semantic design tokens.
- Shared UI primitives must remain generic and context-free.
- shadcn-style components are project-owned code.
- Radix should be used for accessibility-sensitive primitives when needed.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions, visibility, and auditability must be designed early.
- Compact output must not remove security, permission, audit, source-of-truth, frontend-auth, frontend-style, or transaction-boundary warnings when they matter.
- Graph output must use directed edges and meaningful relationship labels.
- Do not claim guaranteed token reduction ratios for compact or graph output.
