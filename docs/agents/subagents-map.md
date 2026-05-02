# Subagents Map

## Purpose

This document defines when AI agents should use specialized subagents while working on Boardly.

`AGENTS.md` is the entrypoint.
This file is the routing map for specialized work.

Before using any subagent, the agent must read:

```text
docs/architecture/project-architecture-rules.md
```

For important architecture decisions, also read:

```text
docs/adr/0000-template.md
```

---

## Core Rule

Use subagents by responsibility, not by framework layer.

Avoid this split:

- Controller Agent;
- Entity Agent;
- Repository Agent.

Prefer this split:

- Product & Domain Analyst;
- DDD / Domain Modeling;
- Workflow & Permissions;
- Symfony Architecture;
- Async / Messaging;
- Search & Read Models;
- Cache & Performance;
- Testing & QA;
- Security;
- DevOps / Observability;
- ADR Writer.

Subagents must improve decision quality, not create extra abstraction.

---

## Subagent Routing Table

| Situation | Use subagent | Main output |
|---|---|---|
| Business feature is unclear | Product & Domain Analyst | Use case, actors, acceptance criteria, MVP boundary |
| Need aggregate/entity/value object design | DDD / Domain Modeling | Aggregates, invariants, domain events, transaction boundary |
| Issue status, lifecycle, transition rules | Workflow & Permissions | Statuses, transitions, guards, permissions, failure cases |
| Authorization, roles, visibility | Workflow & Permissions + Security | Permission model, voters/policies, access risks |
| Symfony folder/module structure | Symfony Architecture | Module layout, layers, ports/adapters direction |
| Controller/application/domain placement | Symfony Architecture | Correct layer placement and dependency direction |
| RabbitMQ, Messenger, async jobs | Async / Messaging | Message contracts, handlers, retries, DLQ, idempotency |
| Reliable event publishing | Async / Messaging + ADR Writer | Outbox decision, failure model, ADR draft |
| OpenSearch / Elasticsearch | Search & Read Models | Index documents, indexing flow, consistency model, reindexing |
| Redis/cache/performance | Cache & Performance | Cache keys, TTLs, invalidation, fallback behavior |
| Tests for feature or module | Testing & QA | Unit/application/integration/async/security test plan |
| Auth/security-sensitive changes | Security | RBAC/ABAC rules, voters, audit-sensitive paths |
| Workers, queues, deployment, metrics | DevOps / Observability | Health checks, logs, metrics, retries, operational risks |
| Important architecture decision | ADR Writer | ADR using `docs/adr/0000-template.md` |
| Conflict between approaches | ADR Writer + relevant domain subagent | Alternatives, trade-offs, recommendation |

---

## Subagents

### Product & Domain Analyst

Use when:

- feature scope is unclear;
- business process needs clarification;
- MVP vs later scope must be separated;
- user story or acceptance criteria are needed.

Should produce:

- actors;
- business flow;
- commands and queries;
- acceptance criteria;
- edge cases;
- MVP recommendation.

Should not produce:

- database schema first;
- Symfony controllers first;
- enterprise features without business need.

---

### DDD / Domain Modeling

Use when:

- designing aggregates;
- defining invariants;
- deciding entity vs value object;
- defining domain events;
- deciding transaction boundaries.

Should produce:

- bounded context;
- aggregate boundaries;
- entities;
- value objects;
- domain services/policies if needed;
- domain events;
- repository ports if needed;
- risks and trade-offs.

Must respect:

```text
docs/architecture/project-architecture-rules.md
```

---

### Workflow & Permissions

Use when:

- issue lifecycle is involved;
- status transition is involved;
- workflow guards are involved;
- role/project membership/ownership rules are involved.

Should produce:

- statuses;
- transitions;
- guards;
- validators;
- permission checks;
- failure cases;
- audit requirements.

Primary scenario:

```text
ChangeIssueStatus
```

---

### Symfony Architecture

Use when:

- translating architecture to Symfony project structure;
- deciding where code should live;
- designing module boundaries;
- choosing Symfony components;
- reviewing whether implementation violates Hexagonal Architecture.

Should produce:

- module/layer placement;
- ports and adapters;
- service wiring direction;
- Symfony component recommendation;
- anti-pattern warnings.

Should not make Symfony mechanics the architecture reason.

---

### Async / Messaging

Use when:

- RabbitMQ is involved;
- Symfony Messenger is involved;
- async side effects are needed;
- event publishing reliability matters;
- retries/dead-letter/idempotency must be designed.

Should produce:

- message contract;
- producer;
- consumer;
- retry policy;
- dead-letter behavior;
- idempotency strategy;
- Outbox recommendation if needed.

Must not move core state changes to async messages.

---

### Search & Read Models

Use when:

- OpenSearch / Elasticsearch is involved;
- global search is involved;
- issue/comment/project search is involved;
- read models or projections are needed;
- reindexing strategy is needed.

Should produce:

- index document shape;
- indexing trigger;
- consistency expectations;
- query/filter design;
- reindexing plan;
- fallback behavior.

Must keep DB as source of truth.

---

### Cache & Performance

Use when:

- Redis is involved;
- cache keys or TTLs are needed;
- invalidation strategy is needed;
- hot read paths are involved;
- locks/rate limits are considered.

Should produce:

- cache target;
- key structure;
- TTL;
- invalidation strategy;
- fallback behavior;
- performance risk.

Must not store canonical business state only in Redis.

---

### Testing & QA

Use when:

- feature architecture needs test coverage;
- workflow rules need verification;
- permissions need verification;
- async handlers need idempotency tests;
- search/cache behavior needs integration tests.

Should produce:

- unit tests;
- application tests;
- integration tests;
- async tests;
- security tests;
- regression cases.

Testing priority:

1. Issue lifecycle
2. Workflow transitions
3. Permission checks
4. Async idempotency
5. Search indexing consistency
6. Audit correctness

---

### Security

Use when:

- authorization is involved;
- roles/permissions are involved;
- project visibility is involved;
- issue ownership is involved;
- audit-sensitive actions are involved;
- external integrations or webhooks are involved.

Should produce:

- RBAC/ABAC decision;
- voter/policy design;
- access checks;
- security risks;
- audit requirements.

Security must be designed before controllers rely on it.

---

### DevOps / Observability

Use when:

- queue workers are involved;
- deployment or runtime operations are involved;
- health checks are involved;
- logs/metrics/traces are needed;
- failed message handling is needed;
- reindex/recovery commands are needed.

Should produce:

- operational checklist;
- health checks;
- metrics;
- logs;
- alerting points;
- recovery commands;
- deployment risks.

---

### ADR Writer

Use when:

- decision is architectural;
- alternatives exist;
- trade-offs must be preserved;
- decision affects modules, transactions, infrastructure, or security;
- future contributors need context.

Must use:

```text
docs/adr/0000-template.md
```

Should produce:

- context;
- decision;
- alternatives;
- consequences;
- trade-offs;
- layer impact;
- DDD/EDA/CQRS impact where relevant;
- risks and mitigations.

---

## Multi-Subagent Rules

Use multiple subagents when the task crosses responsibilities.

Examples:

### `ChangeIssueStatus`

Use:

1. Product & Domain Analyst;
2. DDD / Domain Modeling;
3. Workflow & Permissions;
4. Security;
5. Async / Messaging;
6. Search & Read Models;
7. Testing & QA;
8. ADR Writer if a major decision is made.

### `CreateIssue`

Use:

1. Product & Domain Analyst;
2. DDD / Domain Modeling;
3. Symfony Architecture;
4. Search & Read Models;
5. Async / Messaging;
6. Testing & QA.

### `SearchIssues`

Use:

1. Product & Domain Analyst;
2. Search & Read Models;
3. Security;
4. Cache & Performance;
5. Testing & QA.

### `Project Permissions`

Use:

1. Product & Domain Analyst;
2. Workflow & Permissions;
3. Security;
4. Cache & Performance;
5. Testing & QA;
6. ADR Writer if the permission model becomes a platform-level decision.

---

## Escalation to ADR

Create or request an ADR when a decision affects:

- module boundaries;
- aggregate ownership;
- workflow architecture;
- authorization model;
- transaction boundaries;
- event contracts;
- Outbox usage;
- search consistency;
- cache strategy;
- major Symfony component usage;
- infrastructure responsibilities.

Use the ADR template from:

```text
docs/adr/0000-template.md
```

---

## Final Rule

If a subagent needs architecture rules, it must read:

```text
docs/architecture/project-architecture-rules.md
```

Do not duplicate the architecture rules inside subagent prompts.
