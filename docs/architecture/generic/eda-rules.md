# Generic EDA Rules

Status: reusable architecture rulebook  
Scope: project-agnostic Event-Driven Architecture rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Event-Driven Architecture is used for business facts, async side effects, decoupling, and eventual consistency.

EDA is not a replacement for transactions, authorization, or core business rules.

Use EDA when a state change should notify other parts of the system or trigger side effects without coupling the core use case to those side effects.

## 2. Event meaning

An event describes something that already happened.

Event naming shape:

```text
<Thing><PastTenseVerb>
```

Events are facts, not requests.

Do not name events like commands.

Command shape:

```text
<Verb><Thing>
```

Event shape:

```text
<Thing><Happened>
```

## 3. Domain event rules

Domain events represent internal business facts.

Rules:

```text
- Domain events are produced by domain behavior or application use cases after domain behavior.
- Domain events must not depend on queues, transports, or framework message classes.
- Domain events must not perform side effects by themselves.
- Domain events should contain enough data for intended consumers.
- Domain events should not contain secrets or unnecessary sensitive data.
```

## 4. Integration event rules

Integration events are stable contracts for external systems or cross-boundary consumers.

Rules:

```text
- Integration events should be versioned when consumed outside a module/application boundary.
- Integration event schemas should be stable and documented.
- Do not expose internal domain objects directly as integration contracts.
- Keep sensitive data out unless explicitly required and protected.
```

## 5. Async side effect rules

Use async messaging for side effects that can happen after the main transaction:

```text
- notifications;
- email delivery;
- search indexing;
- reporting projections;
- external system synchronization;
- cache warming;
- non-critical exports;
- audit/log side effects where eventual consistency is acceptable.
```

Do not use async messaging for:

```text
- enforcing core business invariants;
- required authorization decisions;
- core state changes that must be visible immediately;
- state changes that the current response depends on;
- replacing a database transaction.
```

## 6. Transactional outbox rule

If a committed state change and event publication must stay consistent, use a transactional outbox.

Correct flow:

```text
Application use case
-> domain behavior
-> persist state
-> store event/outbox record in same DB transaction
-> commit
-> publisher reads outbox
-> dispatches message
-> consumer handles side effect
```

Forbidden flow:

```text
publish message before DB commit
require queue success for DB transaction validity
dispatch from domain entity directly to transport
hide core state changes in async handlers
```

## 7. Idempotency rules

Async handlers must assume at-least-once delivery.

Handlers must be safe under duplicate delivery.

Common idempotency strategies:

```text
- processed-message store;
- event id + handler name key;
- natural unique constraints;
- external idempotency keys;
- checking existing projection/side-effect state before applying.
```

## 8. Handler rules

Async handlers may:

```text
- call external systems;
- update projections/read models;
- update search indexes;
- send notifications;
- write operational/audit logs;
- perform retryable side effects.
```

Async handlers must not:

```text
- enforce primary business invariants;
- become the only place where core state changes happen;
- assume exactly-once delivery;
- expose secrets in logs/messages;
- silently swallow permanent failures without observability.
```

## 9. Failure handling rules

Async failures must be observable.

Define:

```text
- retry policy;
- failure transport/dead-letter behavior;
- logging context;
- alerting/inspection process;
- manual retry strategy where needed.
```

A failed async side effect must not rollback already committed source-of-truth state.

## 10. Anti-patterns

Avoid:

```text
- events named as commands;
- sending messages directly from controllers;
- dispatching domain events directly from entities to queues;
- async handlers required for core transaction validity;
- non-idempotent consumers;
- generic event payloads without schema ownership;
- one central event mapper that knows every module-specific event;
- leaking internal domain objects as public integration messages.
```
