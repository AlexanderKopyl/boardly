# ADR-0003: Use Transactional Outbox for Domain Event Delivery

## Status

Accepted

## Date

2026-05-02

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / Symfony owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

ADR-0001 established Boardly as a Symfony-based Modular Monolith using Hexagonal Architecture and DDD.

ADR-0002 established the source code structure under `src/Boardly` and `src/Shared`.

The first deep architecture validation scenario is:

```text
ChangeIssueStatus
```

This scenario changes the status of an Issue and produces the business fact:

```text
IssueStatusChanged
```

That fact is needed by several asynchronous consumers:

- search indexing;
- audit / activity projections;
- notifications;
- board / read-model updates;
- reporting projections;
- future integrations.

The core status change must be reliable and immediately valid after the database transaction commits.

At the same time, RabbitMQ, Messenger workers, OpenSearch, notification providers, and async consumers must not be required for the status change itself to be valid.

Without an explicit decision, the project risks introducing unreliable event publication patterns:

- persisting Issue status and then failing before publishing a message;
- publishing a message before the database transaction commits;
- dispatching Messenger messages directly from entities or domain services;
- hiding event publication in Doctrine listeners;
- making RabbitMQ availability part of the core business transaction;
- making async side effects inconsistent without retry/deduplication strategy.

## Decision

We will use the Transactional Outbox Pattern for reliable domain event delivery.

Domain events that must trigger asynchronous side effects are stored durably in an outbox table in the same database transaction as the business state change.

A separate outbox publisher later reads unpublished outbox records and publishes them to Messenger/RabbitMQ.

The successful core business transaction must not depend on RabbitMQ, Messenger workers, Redis, OpenSearch, or external notification services being available.

Initial event requiring this pattern:

```text
IssueStatusChanged
```

Initial use case requiring this pattern:

```text
ChangeIssueStatus
```

## Scope

This decision applies to:

- Boardly backend;
- all bounded contexts that produce durable domain events;
- command handlers that mutate core business state and need async side effects;
- event delivery from PostgreSQL-backed business transactions to Messenger/RabbitMQ;
- search indexing events;
- audit / activity projection events;
- notification events;
- reporting projection events;
- future integration event publication if needed.

This decision does not apply to:

- purely in-memory domain notifications inside a single method call;
- framework-level Symfony events that are not business facts;
- non-critical telemetry/logging;
- cache invalidation that can be safely recomputed without durable event delivery;
- frontend event handling;
- external event contract versioning by itself.

## Architectural Rules

- PostgreSQL remains the source of truth for canonical business state.
- Domain events describe business facts that already happened.
- Domain events that trigger reliable async side effects must be stored in the outbox inside the same DB transaction as the business mutation.
- Command handlers may store domain events through an outbox port as part of the application transaction.
- Domain entities must not publish Messenger/RabbitMQ messages.
- Domain services must not publish Messenger/RabbitMQ messages.
- Doctrine listeners must not hide important business event publication.
- RabbitMQ/Messenger publishing must happen after commit through outbox infrastructure.
- RabbitMQ availability must not be required for a core state change to be valid.
- Async consumers must be idempotent.
- Outbox publishing must support retry.
- Failed async side effects must not roll back already committed core state.
- Integration events, when introduced, must be explicit and versioned separately from internal domain events.

## Alternatives Considered

### Option A: Direct Messenger/RabbitMQ dispatch inside command handler

Pros:

- Simple to implement initially.
- Uses Symfony Messenger directly.
- Fewer moving parts at the beginning.

Cons:

- Message dispatch and database commit can become inconsistent.
- A DB transaction may commit while message dispatch fails.
- A message may be published before the transaction commits.
- RabbitMQ availability can accidentally become required for the use case.
- Harder to guarantee reliable async side effects.
- Retries may duplicate business operations instead of only retrying publication.

### Option B: Doctrine listeners/subscribers publish messages after flush

Pros:

- Keeps command handlers shorter.
- Centralizes some persistence-related hooks.
- Can feel convenient in Doctrine-heavy applications.

Cons:

- Hides business-relevant event publication in infrastructure hooks.
- Makes behavior harder to understand and test.
- Can dispatch messages before the transaction is safely committed.
- Couples business event delivery to Doctrine lifecycle details.
- Encourages side effects outside explicit application use-case flow.

### Option C: Transactional Outbox

Pros:

- Business state and event record are committed atomically.
- RabbitMQ/Messenger availability is not required during the business transaction.
- Publication can be retried safely after commit.
- Async side effects become eventually consistent but reliable.
- Fits Modular Monolith, Hexagonal Architecture, and DDD.
- Keeps domain model independent from transport infrastructure.

Cons:

- Requires an outbox table and publisher process.
- Requires deduplication/idempotency rules for consumers.
- Adds operational complexity for monitoring stuck outbox records.
- Event publication is eventually consistent, not immediate.

## Consequences

### Positive

- Core business mutations remain reliable even if RabbitMQ is unavailable.
- Domain event records are not lost when the business transaction commits.
- Async side effects can be retried independently.
- Event delivery is explicit and testable at the application boundary.
- Domain services and entities stay free from infrastructure concerns.
- Search, audit, notification, board/read model, and reporting updates can be eventually consistent.

### Negative

- Additional infrastructure is required.
- More code is needed for outbox storage, publisher, retry, and cleanup.
- Developers must understand the difference between storing an event and publishing an event.
- Consumers must be idempotent because duplicate delivery is possible.
- Observability must include outbox lag and failed publication monitoring.

### Neutral / Operational

- Outbox records are stored in the primary database.
- Queue workers remain part of the same deployable Symfony application at the current modular monolith stage.
- Outbox publishing may run as a Symfony Console command, Messenger worker, or scheduled process.
- OpenSearch, Redis, notification providers, and reporting projections remain derived/side-effect systems.

## Trade-offs

- We accept eventual consistency for async side effects in exchange for reliable core state changes.
- We accept an outbox table and publisher infrastructure in exchange for atomic state/event persistence.
- We accept possible duplicate event delivery in exchange for retryable publication.
- We accept more explicit application-layer orchestration in exchange for avoiding hidden Doctrine listener behavior.
- We accept delayed search/notification/reporting updates in exchange for keeping PostgreSQL as source of truth.

## Impact on Layers

### Domain

- Domain owns business events as facts, for example `IssueStatusChanged`.
- Domain events must not know Messenger, RabbitMQ, Doctrine, Symfony, Redis, or OpenSearch.
- Domain entities do not dispatch events.
- Domain services may create domain events as part of domain behavior and return them to the application layer.

Example direction for `ChangeIssueStatus`:

```text
IssueStatusChanger creates IssueStatusChanged and returns it in IssueStatusChangeResult.
```

### Application

- Application handlers own use-case orchestration and transaction boundary.
- Application handlers store returned domain events through an outbox port inside the same transaction as aggregate persistence.
- Application layer depends on an outbox abstraction, not on Messenger/RabbitMQ directly.

Initial shared port:

```text
src/Shared/Application/Outbox/OutboxInterface.php
```

Conceptual API:

```text
OutboxInterface::store(array $events): void
```

Where:

```text
$events = DomainEvent[]
```

### Infrastructure

- Infrastructure implements outbox persistence.
- Infrastructure implements outbox publishing to Messenger/RabbitMQ.
- Infrastructure maps domain events into serialized outbox records.
- Infrastructure handles retry, deduplication support, and marking records as published/failed.
- Infrastructure may use Doctrine/DBAL for outbox storage.
- Infrastructure may use Symfony Messenger for publication.

### Interfaces / UI

- Controllers do not publish messages directly.
- Controllers do not know about outbox implementation details.
- Controllers only call application commands/queries and map success/failure to transport responses.

## Impact on DDD

This ADR preserves domain boundaries by keeping event publication infrastructure outside the domain model.

Domain events remain part of the domain language.

Examples:

```text
IssueStatusChanged
IssueCreated
IssueAssigned
ProjectArchived
WorkflowTransitionChanged
```

Domain events are owned by the context where the business fact happens.

For `ChangeIssueStatus`:

```text
IssueStatusChanged is owned by Issues context.
```

The shared domain event contract belongs to Boardly shared kernel:

```text
src/Boardly/SharedKernel/Domain/Event/DomainEvent.php
```

The outbox mechanism itself is not Boardly business language. It is technical application/infrastructure support and belongs under `src/Shared`.

## Impact on EDA

This ADR formalizes reliable event delivery for internal domain events.

Initial flow:

```text
1. Application handler executes business use case.
2. Domain behavior creates domain event.
3. Handler saves aggregate and stores domain event in outbox inside the same DB transaction.
4. Transaction commits.
5. OutboxPublisher reads unpublished outbox records.
6. OutboxPublisher publishes messages to Messenger/RabbitMQ.
7. Async consumers handle side effects.
8. Outbox record is marked as published or retried on failure.
```

Initial async consumers for `IssueStatusChanged` may include:

```text
- search indexing consumer
- audit/activity projection consumer
- notification consumer
- board/read-model projection consumer
- reporting projection consumer
```

Idempotency is required because publication or consumption may happen more than once.

Internal domain events are not the same as public integration events.

Current decision:

```text
Domain event: IssueStatusChanged
Integration event: not introduced yet
```

Future direction:

```text
If external integrations or independently deployed services require stable contracts,
introduce versioned integration events such as IssueStatusChangedV1.
```

## Impact on CQRS

Command handlers can write aggregate state and outbox records atomically.

Read models and projections can be updated asynchronously from published events.

For `ChangeIssueStatus`:

```text
Command side:
- validate permission
- validate workflow transition
- change Issue status
- persist Issue
- store IssueStatusChanged in outbox

Read side:
- search index updated asynchronously
- board/read model updated asynchronously
- reporting projection updated asynchronously
- audit/activity projection updated asynchronously
```

Command handlers should not return complex read models just because projections are eventually consistent.

If clients need the latest canonical state, they should read from the database-backed query model or reload the issue after command success.

## Symfony Implementation Direction

Symfony mechanisms are implementation details.

Likely implementation pieces:

- Doctrine/DBAL for outbox table storage;
- Symfony Messenger for publishing outbox messages to RabbitMQ;
- Symfony Console command or worker for outbox publishing;
- Messenger retry/failure transport for failed async consumers;
- Monolog/metrics for outbox lag and failure monitoring.

Possible outbox record fields:

```text
id
aggregate_type
aggregate_id
event_type
event_id
payload
occurred_at
available_at
published_at
attempts
last_error
created_at
```

This ADR does not finalize database schema.

Implementation must keep these boundaries:

```text
Domain -> no Symfony Messenger
Domain -> no RabbitMQ
Domain -> no Doctrine outbox table
Application -> OutboxInterface
Infrastructure -> Doctrine/DBAL outbox adapter and Messenger publisher
```

## Risks

- Outbox table grows without cleanup.
- Publisher gets stuck and async side effects lag behind.
- Consumers are not idempotent and duplicate side effects happen.
- Developers bypass outbox and dispatch Messenger messages directly from command handlers.
- Event payload schema evolves without compatibility strategy.
- Operational monitoring does not detect failed or delayed outbox records.
- Outbox publishing order assumptions become hidden business requirements.

## Mitigations

- Add cleanup/archive policy for published outbox records.
- Monitor outbox lag, failed attempts, and oldest unpublished record age.
- Require idempotency for every async consumer.
- Add code review rule: business domain events that require reliable delivery go through outbox.
- Keep event payloads explicit and add versioning when exposing integration events.
- Do not rely on strict global event ordering unless a separate ADR defines it.
- Add tests for transaction rollback when outbox storage fails.
- Add tests for publisher retry and consumer idempotency.

## Migration / Adoption Plan

1. Introduce shared domain event contract under `src/Boardly/SharedKernel/Domain/Event/DomainEvent.php` when the first domain event is implemented.
2. Introduce `OutboxInterface` under `src/Shared/Application/Outbox/OutboxInterface.php`.
3. Implement outbox persistence adapter in infrastructure.
4. Create outbox table migration.
5. Update `ChangeIssueStatusHandler` to save Issue and store returned domain events in one transaction.
6. Implement outbox publisher to publish records to Messenger/RabbitMQ after commit.
7. Implement initial consumers for search, audit/activity, notification, board/read model, and reporting only when those projections/features exist.
8. Add monitoring for outbox lag and failed attempts.
9. Add cleanup/archive mechanism for published records.

## Open Questions

- What exact outbox table schema should be used?
- Should outbox records be published by a dedicated Symfony Console command or by a Messenger worker?
- What serialization format should be used for event payloads?
- Should internal domain event payloads be versioned immediately or only integration events?
- How long should published outbox records be retained?
- What retry/backoff strategy should the outbox publisher use?
- How should poison outbox records be marked and inspected?
- Do any future use cases require strict per-aggregate event ordering?
- Should integration events be stored in the same outbox table or mapped from domain events into a separate integration outbox later?

## References

- Related ADRs:
  - `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
  - `docs/adr/0002-use-boardly-context-based-source-structure.md`
- Related docs:
  - `docs/architecture/project-architecture-rules.md`
  - `docs/design/change-issue-status.md`
  - `docs/adr/0000-template.md`
- Related issues:
  - `https://github.com/AlexanderKopyl/boardly/issues/3`
- Related PRs: none

## Review Checklist

- [x] Decision is clear and testable.
- [x] Business context is explained.
- [x] Alternatives are documented.
- [x] Trade-offs are explicit.
- [x] Layer impact is described.
- [x] DDD impact is described.
- [x] EDA impact is described if events are involved.
- [x] CQRS impact is described if commands/queries/read models are involved.
- [x] Symfony implementation is treated as implementation detail.
- [x] Risks and mitigations are documented.
