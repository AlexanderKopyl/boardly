# Boardly Codex Skills

Project skills are reusable execution playbooks for Boardly subagents.

## Rule
Agents define roles. Skills define repeatable workflows.

To avoid duplicated behavior:

- keep product/architecture procedures inside `.codex/skills/*/SKILL.md`;
- keep subagent files focused on role, scope, and skill selection;
- when a request matches a skill, the subagent should follow the skill workflow instead of reinventing the response structure.

## Skills

| Skill | Use for |
| --- | --- |
| `domain-modeling` | aggregates, invariants, value objects, domain events, transaction boundaries |
| `feature-architecture` | end-to-end architecture for a feature/use case |
| `workflow-design` | issue statuses, transitions, guards, validators, Symfony Workflow fit |
| `async-flow` | RabbitMQ/Messenger, Outbox, retries, DLQ, idempotency |
| `search-indexing` | OpenSearch/Elasticsearch documents, projections, reindexing |
| `permission-modeling` | RBAC/ABAC, project membership, issue visibility, voters/policies |
| `testing-strategy` | domain/application/integration/async/security test design |
| `cache-performance` | Redis, TTL, invalidation, hot paths, DB query risks |
| `observability-operations` | health checks, queues, logs, metrics, recovery commands |
| `adr-writing` | architecture decision records and trade-off documentation |

## Global Boardly rules

- Business behavior first, framework mechanics second.
- Modular monolith by default.
- No microservices unless there is a strong operational/product reason.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ is for asynchronous side effects, not core consistency.
- Commands represent user intentions.
- Queries do not mutate state.
- Domain events represent business facts.
- Integration events are stable external contracts.
- Async handlers must be idempotent.
- Controllers stay thin.
- Domain logic must not be hidden in Doctrine listeners.
- Permissions and auditability must be designed early.
