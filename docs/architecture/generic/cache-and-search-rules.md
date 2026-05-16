# Generic Cache And Search Rules

Status: reusable architecture rulebook  
Scope: project-agnostic cache and search rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Cache and search systems improve performance and query capabilities.

They are not the source of truth for durable business state.

## 2. Cache rule

Cache is a performance optimization.

Rules:

```text
- Cache is not the source of truth.
- Cached data must be rebuildable.
- Cache entries should usually have TTLs.
- Cache invalidation must be defined when stale data can harm user behavior.
- Cache failure should degrade performance, not corrupt durable state.
```

## 3. Cache usage

Cache may be used for:

```text
- expensive read results;
- computed view data;
- short-lived counters;
- rate limiting;
- temporary workflow state when acceptable;
- distributed locks when justified;
- external API response caching when allowed.
```

Do not use cache for:

```text
- canonical business state;
- only copy of important data;
- state that must survive cache eviction;
- authorization truth unless it is backed by source-of-truth verification and invalidation rules.
```

## 4. Cache invalidation rule

Every cache must define at least one of:

```text
- TTL-based expiration;
- event-based invalidation;
- versioned cache keys;
- explicit delete/update after write;
- rebuild strategy.
```

If invalidation is unclear, do not cache yet.

## 5. Locking rule

Distributed locks may be used only when there is a concrete concurrency problem.

Rules:

```text
- Locks must have expiration.
- Lock acquisition failure behavior must be explicit.
- Locks must not replace database constraints for durable invariants.
- Locks must not create hidden global bottlenecks without justification.
```

## 6. Search rule

Search engines are read-side systems.

Rules:

```text
- Search index is not the source of truth.
- Search data may be eventually consistent.
- Search documents must be rebuildable from source-of-truth data.
- Search consumers/indexers must be idempotent.
- Search results must respect access control.
- Source-of-truth data wins when search and database disagree.
```

## 7. Search usage

Search may be used for:

```text
- full-text search;
- autocomplete;
- filtering and faceting;
- denormalized read models;
- read-heavy projections;
- relevance-ranked queries.
```

Do not use search for:

```text
- canonical writes;
- enforcing business invariants;
- permission truth;
- state that cannot be reconstructed.
```

## 8. Indexing rule

Search indexing should usually happen asynchronously after source-of-truth commit.

Reliable indexing should use event/outbox-based publication when consistency matters.

Indexing handlers must be idempotent.

## 9. Access control rule

Search results must never expose data that the source-of-truth access model would deny.

Access control may be enforced by:

```text
- filtering query by access context;
- indexing access metadata;
- post-filtering through source-of-truth checks when result size allows it;
- rebuilding indexes when access model changes.
```

The chosen strategy must be documented for sensitive data.

## 10. Anti-patterns

Avoid:

```text
- cache as canonical storage;
- search as canonical storage;
- no TTL and no invalidation strategy;
- indexing before database commit;
- non-idempotent index consumers;
- search results that bypass authorization;
- adding search infrastructure before a real query problem exists.
```
