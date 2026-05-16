# Generic Infrastructure Source Of Truth Rules

Status: reusable architecture rulebook  
Scope: project-agnostic infrastructure and source-of-truth rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Infrastructure tools support the system. They do not define the business model.

This document defines how to treat the relational database, queues, cache, search, files, and external systems.

## 2. Relational database rule

The relational database is the default source of truth for durable business state.

Rules:

```text
- Durable state must be recoverable from the relational database.
- Important invariants should be protected by domain/application rules and database constraints where possible.
- Schema changes must use migrations.
- Indexes must support real access patterns.
- Manual production schema edits are forbidden unless handled through an emergency procedure.
```

## 3. Queue rule

Queues are for asynchronous work and side effects.

Rules:

```text
- Queue is not the source of truth.
- Queue delivery may be delayed.
- Queue delivery may be duplicated.
- Queue consumers must be idempotent.
- Queue outage must not invalidate a committed core DB transaction.
```

Use queues for side effects, projections, integrations, and background jobs.

Do not require queue success for core state changes that must commit synchronously.

## 4. Cache rule

Cache is a performance optimization.

Rules:

```text
- Cache is not the source of truth.
- Cached data must be rebuildable.
- Cache entries should have TTLs unless there is a clear invalidation strategy.
- Cache failure should degrade performance, not corrupt business state.
```

## 5. Search rule

Search engines are read-side tools.

Rules:

```text
- Search index is not the source of truth.
- Search data may lag behind database state.
- Search documents must be rebuildable.
- Search results must respect access control.
- Database state wins when search and database disagree.
```

## 6. File/object storage rule

File/object storage may hold binary or large externalized content.

Rules:

```text
- Metadata that controls business behavior should be in the relational database.
- Files should be addressable through durable metadata.
- Deleting files must be coordinated with durable state.
- External storage operations should be retryable where possible.
```

## 7. External system rule

External systems are not internal source of truth unless the project explicitly defines them as such.

Rules:

```text
- External API failures must be handled explicitly.
- External calls should not be hidden inside domain models.
- External integrations should be behind ports/adapters.
- Synchronization must define conflict and retry behavior.
```

## 8. Infrastructure adapter rule

Infrastructure implements ports and technical mechanisms.

Infrastructure may depend on frameworks, databases, queues, caches, search clients, filesystems, and external SDKs.

Application and Domain should depend on abstractions, not concrete infrastructure classes.

## 9. Observability rule

Infrastructure failures must be observable.

Define:

```text
- structured logs;
- retry policy;
- failure handling;
- dead-letter/failure queues when queues are used;
- health checks where operationally useful;
- metrics/tracing when project maturity requires it.
```

## 10. Anti-patterns

Avoid:

```text
- treating cache as durable storage;
- treating search index as canonical state;
- requiring queue success for synchronous state changes;
- calling external systems directly from domain models;
- storing business-only state in external systems without local representation;
- adding infrastructure abstractions before a real second use case or boundary exists.
```
