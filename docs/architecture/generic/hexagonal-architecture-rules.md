# Generic Hexagonal Architecture Rules

Status: reusable architecture rulebook  
Scope: project-agnostic Ports & Adapters / Hexagonal Architecture rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Hexagonal Architecture separates business/application logic from delivery mechanisms and technical implementation details.

The goal is not to create decorative folders. The goal is to make dependency direction explicit and keep the core system independent from frameworks, databases, queues, caches, search engines, and external APIs.

## 2. Main dependency rule

Target dependency direction:

```text
Interfaces -> Application -> Domain
Infrastructure -> Application / Domain ports
```

Forbidden dependency direction:

```text
Domain -> Infrastructure
Domain -> Interfaces
Domain -> Framework
Application -> Interfaces
Application -> concrete Infrastructure adapters
```

Infrastructure depends inward by implementing ports.

Interfaces depend inward by translating input/output into application commands/queries and responses.

## 3. Layer meaning

### Domain

Contains business behavior, invariants, entities, aggregates, value objects, domain services, domain events, and domain exceptions.

Domain must be independent from framework and infrastructure.

### Application

Contains use cases, orchestration, commands, queries, handlers, application services, ports, results, authorization orchestration, and transaction coordination.

Application controls the workflow of a use case but should not own core business invariants.

### Infrastructure

Contains adapters for persistence, queues, cache, search, filesystem, external APIs, framework services, and technical integrations.

Infrastructure implements ports required by Application or Domain.

### Interfaces

Contains delivery/input/output adapters such as HTTP controllers, CLI commands, request DTOs, response presenters, webhooks, and API documentation concerns.

Interfaces translate transport concerns into application calls.

## 4. Port rules

Ports are interfaces that describe what the core needs.

Ports should be named by capability, not by technology.

Good:

```text
ClockInterface
IdGeneratorInterface
PaymentGatewayInterface
SearchIndexInterface
TransactionalInterface
```

Bad:

```text
RedisServiceInterface
DoctrineHelperInterface
CurlClientInterface
ElasticRawClientInterface
```

A port should expose language useful to the use case, not the API of an external library.

## 5. Adapter rules

Adapters implement ports using specific technology.

Examples of adapter categories:

```text
Doctrine persistence adapter
DBAL query adapter
HTTP API client adapter
Queue publisher adapter
Redis cache adapter
Search index adapter
Filesystem storage adapter
Symfony security adapter
```

Adapters belong in Infrastructure unless they are delivery adapters, in which case they belong in Interfaces.

## 6. Interfaces layer rules

Interfaces may contain:

```text
- HTTP controllers;
- request DTOs;
- response mappers/presenters;
- CLI commands;
- webhook controllers;
- API exception mappers;
- OpenAPI documentation classes.
```

Interfaces must not contain:

```text
- business rules;
- core state mutation logic;
- direct ORM queries;
- direct external API calls;
- queue side-effect orchestration;
- long transaction scripts.
```

## 7. Application layer rules

Application may contain:

```text
- command/query handlers;
- use-case services;
- ports;
- result DTOs;
- transaction orchestration;
- authorization orchestration;
- outbox storage orchestration.
```

Application must not contain:

```text
- HTTP request/response classes;
- framework controller logic;
- concrete ORM entity manager usage in new code;
- concrete external clients;
- transport-specific message classes;
- JSON response construction.
```

## 8. Infrastructure layer rules

Infrastructure may contain:

```text
- ORM entities and repositories;
- DBAL repositories and queries;
- external API clients;
- cache/search/queue adapters;
- message consumers;
- framework service implementations;
- persistence mappers;
- file storage adapters.
```

Infrastructure must not own core business rules.

It may translate between persistence models and domain/application models.

## 9. Dependency inversion rule

Application and Domain must depend on abstractions when a dependency crosses the core boundary.

Good:

```text
Application Handler -> PaymentGatewayInterface
Infrastructure Adapter -> implements PaymentGatewayInterface
```

Bad:

```text
Application Handler -> ConcretePaymentVendorClient
Domain Service -> FrameworkHttpClient
```

## 10. Boundary crossing rule

When data crosses layers, use explicit models:

```text
HTTP Request DTO -> Application Command/Query -> Domain Value Objects/Entities -> Application Result -> HTTP Response DTO
```

Do not pass framework request objects into Application or Domain.

Do not return ORM entities directly from Application to Interfaces.

Do not expose domain aggregates directly as public API response contracts.

## 11. Legacy migration rule

When a legacy codebase violates these boundaries, classify the code as:

```text
CURRENT_STATE
LEGACY_EXCEPTION
TARGET_RULE
WRONG_AND_MUST_BE_FIXED
```

Do not describe legacy placement as target architecture.

Migration should be incremental and behavior-protected.

## 12. Anti-patterns

Avoid:

```text
- controllers with business logic;
- application handlers returning framework responses;
- domain services calling external APIs;
- infrastructure adapters deciding business policy;
- shared helper classes that know every module;
- ports that mirror third-party library APIs;
- moving files into layers without changing dependencies.
```
