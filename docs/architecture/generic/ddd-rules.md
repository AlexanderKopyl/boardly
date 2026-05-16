# Generic DDD Rules

Status: reusable architecture rulebook  
Scope: project-agnostic DDD rules for backend systems.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Use DDD to model business behavior, language, decisions, and invariants.

Do not use DDD to create decorative folders.

DDD is useful when code needs to express:

```text
- business rules;
- lifecycle transitions;
- invariants;
- policies;
- consistency boundaries;
- domain language;
- behavior that must stay correct regardless of transport or persistence.
```

DDD is not a reason to create empty modules, abstract base classes, generic managers, or folders that do not map to real business capabilities.

## 2. Bounded context rules

A bounded context is a language/model boundary.

A bounded context should usually represent a business capability or a cohesive business model, not a technical action.

Good bounded context signals:

```text
- the area has its own vocabulary;
- the area owns business rules;
- the area has state/lifecycle that must be protected;
- the area changes for reasons different from other areas;
- the area can be understood and tested independently;
- the area has clear integration points with other areas.
```

Weak bounded context signals:

```text
- the name is only a technical operation;
- the folder exists only because a framework generated it;
- the folder contains unrelated services;
- the folder has no domain language;
- the folder is a dumping ground for helpers or processors.
```

Technical operations such as import, export, synchronization, transformation, notification delivery, or external API calls are not automatically bounded contexts.

They may become bounded contexts only if they have their own business lifecycle, rules, history, permissions, scheduling, failures, and product language.

## 3. Aggregate rules

An aggregate is a consistency boundary.

An aggregate protects invariants and exposes behavior through methods.

Aggregate rules:

```text
- Keep aggregate boundaries small.
- Do not put the whole module into one aggregate.
- Do not make every entity an aggregate root.
- Aggregate methods should express business intent.
- Do not expose public setters for important state transitions.
- Do not let controllers or application handlers mutate aggregate internals directly.
```

Good aggregate behavior shape:

```php
$aggregate->performBusinessAction($input, $now);
```

Bad aggregate behavior shape:

```php
$aggregate->setStatus('some_status');
$aggregate->data = $requestData;
```

## 4. Entity rules

Entities have identity and lifecycle.

Use an entity when:

```text
- identity matters over time;
- lifecycle matters;
- state changes through business rules;
- equality is not only value equality.
```

Do not create entities for simple immutable values.

## 5. Value object rules

Value objects represent meaningful values.

Use a value object when a scalar has:

```text
- validation;
- normalization;
- formatting;
- comparison behavior;
- domain meaning;
- units or boundaries;
- invariants.
```

Value object rules:

```text
- immutable by default;
- validates itself;
- framework-independent;
- persistence-independent;
- no service dependencies;
- no HTTP, ORM, queue, cache, or external API dependencies.
```

Do not create value objects for every trivial scalar if it adds no clarity or protection.

## 6. Domain service rules

Use a domain service only when behavior does not naturally belong to one aggregate, entity, or value object.

A domain service may contain business logic.

A domain service must not be an infrastructure adapter.

Good reasons for a domain service:

```text
- rule depends on multiple domain objects;
- policy is reusable across multiple aggregates;
- calculation belongs to the domain but not to one entity;
- validation depends on domain configuration/model, not framework input.
```

Bad reasons:

```text
- avoiding methods on aggregates;
- creating generic Manager/Helper/Utils classes;
- wrapping repositories without adding domain behavior;
- hiding application orchestration in the domain layer.
```

## 7. Domain event rules

Domain events describe facts that already happened.

Rules:

```text
- Events are past-tense facts.
- Events are not commands.
- Events must not depend on transport technologies.
- Events should contain enough data for intended consumers.
- Events should not expose secrets or unnecessary personal data.
```

Good event name shape:

```text
<ThingHappened>
```

Bad event name shape:

```text
<DoSomething>
```

## 8. Domain exception rules

Domain exceptions represent violated business rules or invalid domain operations.

Rules:

```text
- Domain exceptions must not contain HTTP status codes.
- Domain exceptions must not contain JSON response shapes.
- Domain exceptions must not depend on framework exception classes.
- API/CLI mapping belongs outside the domain.
```

## 9. Repository interface rules

Repository interfaces may live in Domain or Application depending on the project convention.

They must express domain/application needs, not ORM mechanics.

Good repository methods:

```php
public function save(Aggregate $aggregate): void;
public function findById(AggregateId $id): ?Aggregate;
```

Bad repository methods:

```php
public function getEntityManager(): EntityManagerInterface;
public function createQueryBuilder(string $alias): QueryBuilder;
```

## 10. What must not be in Domain

Domain must not contain:

```text
- HTTP controllers;
- request DTOs;
- response DTOs tied to API contracts;
- framework forms;
- templates;
- ORM entity manager usage;
- DBAL connections;
- queue clients;
- cache clients;
- search clients;
- external API clients;
- framework event subscribers;
- application command/query handlers;
- transport-specific message classes.
```

## 11. Placement rule

If a class answers:

```text
Can this business operation happen, and how does the model change?
```

it probably belongs in Domain.

If it answers:

```text
How do we execute this use case?
```

it belongs in Application.

If it answers:

```text
How do we talk to a framework, database, queue, cache, search engine, filesystem, or external API?
```

it belongs in Infrastructure.

If it answers:

```text
How does input/output enter or leave the system?
```

it belongs in Interfaces.

## 12. Anti-patterns

Avoid:

```text
- anemic models with public setters for business state;
- god aggregate roots;
- domain services as procedural transaction scripts;
- generic Manager/Helper/Utils classes;
- putting framework or persistence details into domain classes;
- creating bounded contexts for every technical action;
- creating folders before real use cases exist;
- using DDD vocabulary without business behavior.
```
