# Shared Skill Usage Rules for Boardly Subagents

## Core principle

Subagents define responsibility and judgment. Skills define repeatable execution workflows.

When a user request matches a skill, the subagent must follow that skill instead of duplicating the workflow in its own answer.

## How to choose a skill

| User need | Use skill |
| --- | --- |
| Aggregate, invariant, value object, domain event, repository, transaction boundary | `domain-modeling` |
| End-to-end architecture for a feature/use case | `feature-architecture` |
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

## Multi-skill requests

Use multiple skills when the request crosses boundaries.

Examples:

- `ChangeIssueStatus` usually needs `feature-architecture`, `domain-modeling`, `workflow-design`, `permission-modeling`, `async-flow`, `search-indexing`, `testing-strategy`, and possibly `adr-writing`.
- `SearchIssues` usually needs `feature-architecture`, `search-indexing`, `permission-modeling`, `cache-performance`, and `testing-strategy`.
- `GetProjectBoard` usually needs `feature-architecture`, `search-indexing` or read-model reasoning, `permission-modeling`, `cache-performance`, and `observability-operations`.
- A compact answer to a previously analyzed feature can add `caveman-response` as the final output layer.
- A compact structural map can add `graphify-knowledge-map` after the relevant architecture/domain skill.

## Priority rule

If several skills apply, start with the business architecture skill, then add specialized skills:

1. `feature-architecture`
2. `domain-modeling`
3. `workflow-design` or `permission-modeling`
4. `async-flow`, `search-indexing`, or `cache-performance`
5. `testing-strategy`
6. `observability-operations`
7. `adr-writing`
8. `graphify-knowledge-map` when the user asks for graph output
9. `caveman-response` only as a final compression layer when the user asks for short output

## Global constraints

- Do not assume existing folders, entities, controllers, services, queues, indexes, or database tables unless explicitly provided.
- Boardly starts as a modular monolith.
- Symfony is an implementation framework, not the architectural center.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ is for asynchronous side effects, not core consistency.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions, visibility, and auditability must be designed early.
- Compact output must not remove security, permission, audit, source-of-truth, or transaction-boundary warnings when they matter.
- Graph output must use directed edges and meaningful relationship labels.
- Do not claim guaranteed token reduction ratios for compact or graph output.
