# Generic Symfony Backend Rules

Status: reusable architecture rulebook  
Scope: project-agnostic Symfony backend rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Symfony is a framework. It is not the architecture center.

Symfony provides HTTP, console, dependency injection, validation, security, persistence integration, messaging integration, and configuration mechanisms.

Business architecture should be expressed through modules, layers, use cases, domain models, and ports/adapters.

## 2. Default Symfony folders

Symfony default folders may exist:

```text
config/
public/
migrations/
templates/
translations/
var/
tests/
src/Kernel.php
```

These folders are framework/runtime structure.

They do not define business architecture.

## 3. Avoid framework-first business placement

Avoid creating new modular business code in framework-first folders such as:

```text
src/Controller/
src/Entity/
src/Repository/
src/Service/
src/EventSubscriber/
src/Command/
```

Preferred target placement:

```text
src/<Module>/Interfaces/Http/Controller/
src/<Module>/Infrastructure/Persistence/Doctrine/Entity/
src/<Module>/Infrastructure/Persistence/Doctrine/Repository/
src/<Module>/Application/<UseCase>/
src/<Module>/Infrastructure/EventSubscriber/
src/<Module>/Interfaces/Console/
```

Existing framework-first folders may remain during migration, but new code must not copy legacy placement unless a project-specific decision says otherwise.

## 4. Controller rules

Controllers are delivery adapters.

Controllers belong in the Interfaces layer.

Controllers may:

```text
- receive HTTP requests;
- read route parameters;
- use request DTOs;
- validate transport-level input;
- call application command/query bus or use-case boundary;
- map application results to HTTP responses;
- set headers/cookies when this is transport concern.
```

Controllers must not:

```text
- contain business rules;
- use EntityManager directly;
- execute SQL;
- call external APIs directly;
- mutate domain objects directly;
- dispatch async messages for business side effects directly;
- return raw domain aggregates or ORM entities;
- become long orchestration scripts.
```

## 5. Request DTO and validation rules

Request DTOs belong to Interfaces.

Symfony Validator may be used for transport/input validation.

Request DTO validation is not a replacement for domain invariants.

Request DTOs must not be passed directly into domain models unless intentionally mapped into value objects or commands.

Expected flow:

```text
HTTP Request -> Request DTO -> Application Command/Query -> Domain model/value objects
```

## 6. Application service rules

Application services and handlers must not depend on Symfony HTTP objects.

Forbidden in Application:

```text
Symfony\Component\HttpFoundation\Request
Symfony\Component\HttpFoundation\Response
Symfony\Component\HttpFoundation\JsonResponse
```

Application services may depend on project-owned ports such as transaction, clock, id generation, repositories, gateways, and event/outbox ports.

## 7. Doctrine rules

Doctrine is an Infrastructure tool.

Doctrine entities may be used as persistence models.

Project-specific rules decide whether Doctrine attributes are allowed in domain entities. If allowed in a legacy project, classify it as a compromise or explicit decision, not a universal DDD rule.

New code should avoid leaking Doctrine types into Application and Domain.

Forbidden in Domain:

```text
Doctrine\ORM\EntityManagerInterface
Doctrine\DBAL\Connection
Doctrine\ORM\QueryBuilder
```

Application should not expose Doctrine QueryBuilder through repository ports.

## 8. Messenger rules

Symfony Messenger may be used:

```text
- behind command/query bus abstractions;
- for async message dispatch;
- for integration/event consumers;
- for background jobs.
```

Do not route core synchronous use cases to async transport by default.

Do not require Messenger/RabbitMQ success for a core database state change to be valid.

Application and Domain should not depend directly on Symfony transport details.

## 9. Security rules

Symfony Security is infrastructure/delivery support.

Use it for authentication, security principals, access decisions, and framework integration.

Do not put business authorization exclusively in route attributes or controllers.

Authorization that depends on domain state should be explicit in Application or dedicated policy/voter services, depending on project convention.

## 10. Event subscriber/listener rules

Symfony event subscribers/listeners are framework adapters.

They belong in Infrastructure or Interfaces depending on the event type:

```text
HTTP/kernel/API response concerns -> Interfaces
Doctrine/framework/infrastructure concerns -> Infrastructure
```

They must not hide major business state changes.

## 11. Console command rules

Console commands are entry points.

User/admin/business operation commands belong in Interfaces/Console.

Technical maintenance commands may belong in Infrastructure/Console if they are purely operational.

Console commands should delegate to Application use cases or infrastructure services. They should not contain domain mutation logic directly.

## 12. Serializer rules

Do not blindly serialize domain objects or ORM entities as public API responses.

Use explicit response DTOs, presenters, or mappers for public contracts.

## 13. Configuration rules

Configuration belongs in `config/`.

Configuration must not become a hidden business rule engine unless explicitly designed.

Business rules that require domain behavior should be represented in domain/application code or explicit rule models.

## 14. Migration rule

When migrating legacy Symfony code:

```text
- do not move everything at once;
- protect behavior with tests first;
- move one controller/use case group at a time;
- preserve routes and public contracts unless the task explicitly changes them;
- separate mechanical namespace moves from behavioral refactors.
```
