# Generic CQRS Rules

Status: reusable architecture rulebook  
Scope: project-agnostic CQRS rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

CQRS separates state-changing operations from read operations when that separation makes the system clearer.

CQRS is optional. Use it where it improves use-case clarity, read-model performance, or consistency boundaries.

Do not introduce CQRS folders just for decoration.

## 2. Command rules

Commands represent user or system intent to change state.

Command naming shape:

```text
<Verb><Thing>Command
```

Examples using placeholders:

```text
Create<Aggregate>Command
Update<Aggregate>Command
Archive<Aggregate>Command
Approve<Process>Command
```

Command rules:

```text
- Commands are imperative.
- Commands may contain input required to perform a use case.
- Commands should not contain framework request/response objects.
- Commands should not contain ORM entities.
- Commands should not contain external client objects.
- Commands should be handled synchronously if the caller needs the result immediately.
```

## 3. Command handler rules

Command handlers orchestrate state changes.

Handlers may:

```text
- normalize input;
- construct value objects;
- check authorization through policies/services;
- load aggregates through ports/repositories;
- call domain behavior;
- save through ports/repositories;
- coordinate transactions;
- store outbox events;
- return a small result DTO.
```

Handlers must not:

```text
- return HTTP responses;
- use framework Request/Response;
- expose ORM entities as results;
- call external clients directly when a port should exist;
- perform async side effects before database commit;
- contain large read-model assembly unless explicitly justified.
```

## 4. Query rules

Queries represent a request to read data.

Query naming shape:

```text
<Get/List/Search><Thing>Query
```

Query rules:

```text
- Queries do not mutate state.
- Queries may use read models.
- Queries may use optimized database queries.
- Queries may use search indexes when eventual consistency is acceptable.
- Queries must respect access control.
- Queries must not leak inaccessible data and expect the caller to filter later.
```

## 5. Query handler rules

Query handlers assemble read results.

Handlers may:

```text
- call read repositories;
- use DBAL/optimized read adapters through ports or infrastructure query services;
- use search adapters when appropriate;
- return read DTOs/read models;
- apply access-aware filters.
```

Handlers must not:

```text
- change durable state;
- publish domain events;
- rely on cache/search as source of truth when strong consistency is required;
- return ORM entities as public API response objects.
```

## 6. Result/read model rules

Command results should be small and use-case focused.

Query results may be larger read models if needed.

Do not use domain aggregates as public API response DTOs.

## 7. Read/write model separation

A project may use the same persistence model for reads and writes if it is simple.

Separate read models are justified when:

```text
- list/detail read shape differs heavily from write model;
- performance requires denormalized reads;
- search/filtering requires a specialized index;
- dashboards or projections need precomputed data;
- external API contracts must stay independent from domain/persistence shape.
```

Do not introduce separate read models before they solve a real problem.

## 8. Consistency rules

Read models may be eventually consistent only when the business flow allows it.

Critical state must be confirmable from the relational source of truth.

If a read model conflicts with source-of-truth state, source-of-truth state wins.

## 9. Async CQRS rule

Do not make commands async by default.

Async commands are only acceptable for explicitly background operations where the caller does not require immediate consistency.

Core state-changing commands that return immediate user feedback should be synchronous.

## 10. Anti-patterns

Avoid:

```text
- commands that are actually events;
- queries that mutate state;
- command handlers returning huge read models;
- query handlers performing writes;
- async command routing for core mutations without a product reason;
- CQRS folder structures with no real command/query distinction;
- read models that bypass authorization.
```
