# Generic Transaction Rules

Status: reusable architecture rulebook  
Scope: project-agnostic transaction and consistency rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Transactions protect source-of-truth state and business consistency.

Use transactions for core state changes that must be committed atomically.

Do not use queues, cache, search indexes, or external systems as substitutes for source-of-truth transactions.

## 2. Source-of-truth rule

The relational database is the default source of truth for durable business state.

Hard rules:

```text
- Queue success must not be required for core DB commit validity.
- Cache success must not be required for core DB commit validity.
- Search-index success must not be required for core DB commit validity.
- External API success should not be part of a DB transaction unless explicitly justified.
```

## 3. When a transaction is required

Use a database transaction for:

```text
- creating durable business state;
- changing lifecycle/status/state of durable business objects;
- permission-sensitive mutations;
- account/security-sensitive state changes;
- financial or inventory-like mutations;
- storing state change + outbox event together;
- multi-row consistency updates;
- audit-critical durable writes.
```

## 4. Application transaction boundary

Application layer usually coordinates transaction boundaries.

Expected flow:

```text
Application handler
-> start transaction through transaction port/service
-> load state
-> call domain behavior
-> persist changes
-> store outbox records if needed
-> commit
```

Domain must not start database transactions.

Controllers should not own transaction boundaries for business use cases.

## 5. External side effect rule

External side effects should usually happen after commit.

Examples of side effects:

```text
- sending email;
- publishing queue messages;
- updating search index;
- calling external API;
- warming cache;
- writing non-critical projections.
```

If the side effect must be reliable relative to the state change, use transactional outbox.

## 6. Outbox consistency rule

When event delivery matters:

```text
business state change + outbox record = same DB transaction
```

Do not publish the message directly inside the transaction as the only delivery mechanism.

Do not publish before commit.

## 7. Locking and concurrency rules

Use explicit concurrency strategy when concurrent writes can corrupt state.

Options:

```text
- optimistic locking;
- pessimistic locking;
- unique constraints;
- idempotency keys;
- retry on conflict;
- transaction isolation where justified.
```

Do not add locks without a concrete race condition or consistency requirement.

## 8. Idempotency rules

State-changing operations exposed to retries should be idempotent when possible.

Use idempotency keys for externally retried operations if duplicate execution would be harmful.

Async consumers must be idempotent because delivery is usually at-least-once.

## 9. Doctrine listener rule

Do not hide important business mutations in ORM lifecycle listeners.

Allowed listener usage:

```text
- technical timestamps when project convention accepts it;
- technical metadata;
- infrastructure concerns that do not decide business behavior.
```

Forbidden listener usage:

```text
- changing lifecycle status secretly;
- enforcing primary business rules;
- publishing business events without application awareness;
- calling external systems as hidden side effects.
```

## 10. Anti-patterns

Avoid:

```text
- transaction opened in controller for business flow;
- domain model using EntityManager;
- queue publish before DB commit;
- cache/search write treated as source of truth;
- long-running transaction waiting for external HTTP API;
- hidden business writes in ORM listeners;
- missing transaction around multi-row invariant update.
```
