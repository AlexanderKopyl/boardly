# ADR-0000: <Decision title>

## Status

Proposed | Accepted | Deprecated | Superseded

## Date

YYYY-MM-DD

## Owner

- Architect:
- Domain owner:
- Technical owner:

## Context

Describe the problem, business need, and architectural pressure that forces this decision.

This section must explain:

- what feature, module, or cross-cutting concern is affected;
- why the decision is needed now;
- what constraints exist;
- what is risky if we do not decide explicitly.

Avoid implementation noise here. Focus on the architectural reason.

## Decision

State the decision clearly.

Use direct wording:

> We will ...

The decision must be specific enough that future contributors can follow it without asking the original author.

## Scope

This decision applies to:

- modules:
- bounded contexts:
- layers:
- infrastructure components:

This decision does not apply to:

- ...

## Architectural Rules

List the concrete rules introduced by this ADR.

Example:

- Domain layer must not depend on Symfony, Doctrine, Messenger, Redis, OpenSearch, or RabbitMQ.
- Application layer orchestrates use cases but does not own business invariants.
- Infrastructure adapters implement ports defined by Application or Domain.
- Controllers are delivery adapters and must stay thin.

## Alternatives Considered

### Option A: <name>

Pros:

- ...

Cons:

- ...

### Option B: <name>

Pros:

- ...

Cons:

- ...

## Consequences

### Positive

- ...

### Negative

- ...

### Neutral / Operational

- ...

## Trade-offs

Be explicit about what we accept.

Examples:

- More boilerplate in exchange for clearer module boundaries.
- Eventual consistency in search in exchange for better read performance.
- Modular monolith now in exchange for simpler deployment and refactoring.

## Impact on Layers

### Domain

- ...

### Application

- ...

### Infrastructure

- ...

### Interfaces / UI

- ...

## Impact on DDD

Explain how this decision affects:

- bounded contexts;
- aggregates;
- entities;
- value objects;
- domain services;
- repositories;
- domain events.

## Impact on EDA

Explain whether this decision introduces or changes:

- domain events;
- integration events;
- async handlers;
- message contracts;
- idempotency requirements;
- retry / dead-letter behavior;
- Outbox Pattern need.

## Impact on CQRS

Explain whether this decision affects:

- commands;
- queries;
- read models;
- projections;
- search indexing;
- dashboard data.

## Symfony Implementation Direction

Mention Symfony mechanisms only as implementation details.

Possible examples:

- Messenger;
- Doctrine;
- Security Voters;
- Validator;
- Event Dispatcher;
- Console Commands;
- Cache Contracts;
- Lock Component;
- Workflow Component.

Do not make Symfony mechanics the architectural reason.

## Risks

List direct risks:

- ...

## Mitigations

For each risk, define mitigation:

- ...

## Migration / Adoption Plan

For new decisions:

1. ...
2. ...
3. ...

For changes to existing decisions:

1. ...
2. ...
3. ...

## Open Questions

- ...

## References

- Related ADRs:
- Related docs:
- Related issues:
- Related PRs:

## Review Checklist

- [ ] Decision is clear and testable.
- [ ] Business context is explained.
- [ ] Alternatives are documented.
- [ ] Trade-offs are explicit.
- [ ] Layer impact is described.
- [ ] DDD impact is described.
- [ ] EDA impact is described if events are involved.
- [ ] CQRS impact is described if commands/queries/read models are involved.
- [ ] Symfony implementation is treated as implementation detail.
- [ ] Risks and mitigations are documented.
