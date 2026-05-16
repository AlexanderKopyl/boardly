# Generic Backend Architecture Rules

Status: reusable architecture rulebook  
Scope: Symfony backend projects using Modular Monolith, DDD, Hexagonal Architecture, CQRS where useful, EDA where justified, relational database, queues, cache, and search.

This document is intentionally project-agnostic. It must not contain product-specific bounded contexts, routes, database table names, business entities, or UI assumptions from any concrete project.

Use this document as a baseline for new projects and legacy migrations. Project-specific architecture documents may extend or narrow these rules, but should not silently contradict them.

---

## 1. Purpose

The purpose of this document is to define clear backend architecture placement rules.

It answers:

```text
- what belongs in Domain;
- what belongs in Application;
- what belongs in Infrastructure;
- what belongs in Interfaces;
- how Symfony default folders should be treated;
- how to avoid decorative DDD folders;
- how CQRS, EDA, transactions, cache, search, and queues should be used;
- how AI agents and contributors should classify legacy code versus target architecture.
```

The target architecture is:

```text
Domain
Application
Infrastructure
Interfaces
```

Alternative names such as `Presentation`, `UI`, or `Delivery` may exist in legacy projects, but the preferred target name in this rulebook is `Interfaces`.

---

## 2. Core Architecture

Default backend architecture:

```text
Symfony backend
Modular Monolith
DDD where it models real business behavior
Hexagonal Architecture / Ports & Adapters
CQRS where it simplifies use cases and read models
EDA where async side effects are justified
```

Default dependency direction:

```text
Interfaces -> Application -> Domain
Infrastructure -> Application / Domain ports
```

Forbidden dependency direction:

```text
Domain -> Symfony
Domain -> Doctrine
Domain -> HTTP
Domain -> Redis
Domain -> RabbitMQ
Domain -> OpenSearch / Elasticsearch
Domain -> external API clients
Application -> Symfony Request / Response
Application -> Doctrine EntityManager directly in new code
Application -> concrete infrastructure clients directly
```

Hard rules:

```text
- Relational database is the source of truth.
- Redis is not the source of truth.
- OpenSearch / Elasticsearch is not the source of truth.
- RabbitMQ or any queue must not be required for a core state change to be valid.
- Symfony is an implementation framework, not the architecture center.
- Business behavior must not be placed according to Symfony default folder habits.
```

---

## 3. Target Source Structure

Use a context/module-oriented structure.

Recommended target layout:

```text
src/
  <Module>/
    Domain/
    Application/
    Infrastructure/
    Interfaces/

  Shared/
```

Optional when a project has a product namespace:

```text
src/
  <Product>/
    <Module>/
      Domain/
      Application/
      Infrastructure/
      Interfaces/

  Shared/
```

Do not mix multiple target layouts without an explicit architecture decision.

### Module rule

A module or bounded context should represent a business capability, not a technical action.

Good module candidates:

```text
Catalog
Orders
Customers
Payments
Delivery
Content
Users
Subscriptions
Billing
Notifications
Search
Reporting
Audit
Integrations
Automation
```

Bad module candidates when used blindly:

```text
Import
Export
Sync
Helpers
Services
Managers
Common
Utils
Processors
```

`Import`, `Export`, and `Sync` are usually use cases, jobs, workflows, or infrastructure concerns inside a real business module.

Example anti-pattern:

```text
src/Application/Catalog/Import
```

Why it is wrong:

```text
- Catalog is a business module, not a subfolder of Application at the root.
- Import is not automatically a bounded context.
- The placement hides Domain, Application, Infrastructure, and Interfaces boundaries.
```

Preferred direction:

```text
src/Catalog/Application/ImportProducts/
  ImportProductsCommand.php
  ImportProductsHandler.php
  ImportProductsResult.php

src/Catalog/Domain/
  Product.php
  ProductId.php
  ProductName.php

src/Catalog/Infrastructure/Import/
  CsvProductImportReader.php
  ExternalCatalogImportClient.php
  DoctrineProductRepository.php

src/Catalog/Interfaces/Console/
  ImportProductsConsoleCommand.php
```

If importing becomes a cross-module product capability with its own lifecycle, rules, scheduling, history, and permissions, then a separate `Imports` or `Integrations` module may be justified through an explicit architecture decision.

---

## 4. Symfony Default Folder Rules

Symfony default folders are framework convenience, not architecture.

Allowed default Symfony locations:

```text
config/
public/
migrations/
templates/          only if the project uses Twig directly
translations/
var/
tests/
src/Kernel.php
```

Avoid using these as product-code targets in new modular code:

```text
src/Controller/
src/Entity/
src/Repository/
src/Service/
src/EventSubscriber/
src/Command/
```

Reason:

```text
They organize code by framework mechanism, not by business capability.
```

Target alternatives:

```text
src/<Module>/Interfaces/Http/Controller/
src/<Module>/Infrastructure/Persistence/Doctrine/Entity/
src/<Module>/Infrastructure/Persistence/Doctrine/Repository/
src/<Module>/Application/<UseCase>/
src/<Module>/Infrastructure/EventSubscriber/
src/<Module>/Interfaces/Console/
```

Legacy exception rule:

```text
Existing framework-style folders may remain temporarily during migration, but they must be classified as legacy/current state, not target architecture.
New code must not copy legacy placement.
```

---

## 5. Domain Layer Rules

Domain owns business behavior and invariants.

Domain may contain:

```text
- Aggregates
- Entities
- Value Objects
- Domain Services
- Domain Policies / Specifications
- Domain Events
- Domain Exceptions
- Domain Result objects when useful
- Repository interfaces only if they express domain collection needs
```

Domain answers:

```text
- Is this business operation valid?
- How does the model change?
- Which invariant must always hold?
- What business fact happened?
```

Domain must not contain:

```text
- HTTP controllers
- Symfony Request / Response
- JsonResponse
- Forms
- Twig
- Doctrine EntityManager
- DBAL Connection
- external API clients
- Redis clients
- RabbitMQ / Messenger transports
- OpenSearch / Elasticsearch clients
- application command handlers
- infrastructure adapters
- framework event subscribers
```

Good:

```php
$order->place($customerId, $now);
$project->archive($now);
$product->rename($newName);
```

Bad:

```php
$order->setStatus('placed');
$project->status = 'archived';
$product->name = $request->request->get('name');
```

### Value Object rules

Use value objects when a scalar has domain meaning, validation, normalization, or behavior.

Good candidates:

```text
Email
Money
OrderId
ProjectId
ProductCode
Quantity
Slug
DateRange
```

Rules:

```text
- Value objects are immutable.
- Validate invariants in constructors or named constructors.
- Do not create value objects for every trivial scalar.
- Do not let value objects depend on Symfony, Doctrine, HTTP, or external services.
```

### Domain Service rules

Use domain services only when behavior does not naturally belong to one aggregate/entity/value object.

Good candidates:

```text
PricingPolicy
AvailabilityPolicy
WorkflowTransitionPolicy
ShippingCostPolicy
```

Bad candidates:

```text
OrderManager
ProductHelper
CatalogUtils
BaseDomainService
```

---

## 6. Application Layer Rules

Application owns use-case orchestration.

Application may contain:

```text
- Commands
- Queries
- Command Handlers
- Query Handlers
- Application Services
- Ports / Interfaces
- Use-case Result DTOs
- Transaction coordination
- Authorization orchestration
- Outbox storage coordination
```

Typical structure:

```text
src/<Module>/Application/<UseCase>/
  <UseCase>Command.php
  <UseCase>Handler.php
  <UseCase>Result.php

src/<Module>/Application/<ReadUseCase>/
  <ReadUseCase>Query.php
  <ReadUseCase>Handler.php
  <ReadUseCase>Result.php
```

Application answers:

```text
- What use case is being executed?
- Who is executing it?
- What aggregate/model should be loaded?
- Which domain behavior should be called?
- What should be persisted?
- Which side effects should be scheduled after commit?
- What safe result should be returned?
```

Application handlers may:

```text
- normalize input through value objects;
- load aggregates through repository ports;
- check application-level conflicts;
- coordinate authorization through policies/services;
- call domain behavior;
- coordinate transactions;
- persist through ports;
- store outbox events when reliable async side effects are required;
- return result DTOs/read models.
```

Application handlers must not:

```text
- know Symfony Request / Response;
- return JsonResponse;
- create HTTP cookies;
- use Doctrine EntityManager directly in new code;
- call external API clients directly;
- call Redis/OpenSearch/RabbitMQ clients directly;
- dispatch Symfony MessageBusInterface directly from new use cases;
- contain raw SQL directly unless explicitly documented as legacy/technical exception;
- expose Doctrine entities or domain aggregates as public API responses.
```

Application ports should describe what the use case needs, not which tool provides it.

Good:

```php
interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(ProductId $id): ?Product;
}
```

Bad:

```php
interface ProductRepositoryInterface
{
    public function createQueryBuilder(string $alias): QueryBuilder;
    public function getEntityManager(): EntityManagerInterface;
}
```

---

## 7. Infrastructure Layer Rules

Infrastructure implements technical adapters.

Infrastructure may contain:

```text
- Doctrine entities
- Doctrine repositories
- DBAL queries
- migrations-related persistence code
- external API clients
- payment provider adapters
- delivery provider adapters
- mailer adapters
- filesystem adapters
- Redis adapters
- OpenSearch / Elasticsearch adapters
- Messenger messages and handlers
- queue producers/consumers
- framework event listeners/subscribers
- security adapters
- cache implementations
```

Infrastructure answers:

```text
- How is this persisted?
- How is this external system called?
- How is this message consumed?
- How is this cache/search adapter implemented?
- How does this integrate with Symfony/Doctrine/DBAL/Messenger?
```

Infrastructure must not own core business rules.

Infrastructure depends inward:

```text
Infrastructure -> Application port
Infrastructure -> Domain model/value objects
```

Domain and Application must not depend on concrete Infrastructure classes.

---

## 8. Interfaces Layer Rules

Interfaces is the delivery/input/output layer.

Interfaces may contain:

```text
- HTTP controllers
- API controllers
- Admin HTTP controllers
- Storefront controllers
- Request DTOs
- Response DTOs / Presenters
- Form adapters
- Webhook controllers
- Console commands that act as user/admin entry points
- HTTP exception mappers/subscribers
- OpenAPI schemas/attributes when tied to HTTP contract
```

Suggested structure:

```text
src/<Module>/Interfaces/Http/Controller/
src/<Module>/Interfaces/Http/Request/
src/<Module>/Interfaces/Http/Response/
src/<Module>/Interfaces/Http/Admin/
src/<Module>/Interfaces/Http/Api/
src/<Module>/Interfaces/Http/Storefront/
src/<Module>/Interfaces/Console/
src/<Module>/Interfaces/Webhook/
```

Interfaces answers:

```text
- How does input enter the system?
- How is request data mapped to command/query?
- How is application result mapped to response?
- How are HTTP status codes and headers represented?
```

Controllers may:

```text
- receive HTTP requests;
- validate transport-level input through request DTOs;
- read route parameters, headers, and cookies as transport concerns;
- map request DTOs to commands/queries;
- call command/query bus or application use case boundary;
- map result DTOs to JSON/HTML/redirect responses.
```

Controllers must not:

```text
- contain business rules;
- mutate aggregates directly;
- use Doctrine EntityManager;
- execute SQL;
- call external APIs directly;
- dispatch async messages directly for business side effects;
- own permission decisions beyond transport-level guards;
- become large orchestration services.
```

Target controller placement rule:

```text
All new HTTP controllers belong under Interfaces/Http.
Application must not contain controllers.
Infrastructure must not contain controllers.
Domain must not contain controllers.
```

---

## 9. Shared Code Rules

`Shared` is for technical or architecture-level reusable code that does not know the product language.

Allowed in `Shared`:

```text
CommandBusInterface
QueryBusInterface
TransactionalInterface
ClockInterface
UuidGeneratorInterface
Outbox infrastructure contracts
generic API error envelope helpers
technical exception base classes
```

Forbidden in `Shared`:

```text
Product-specific aggregates
Product-specific domain events
Product-specific permissions
Product-specific repositories
Product-specific mappers
Product-specific status enums
```

If it speaks a module's business language, it belongs in that module.

Do not make `Shared` a dumping ground.

---

## 10. CQRS Rules

CQRS is allowed when it makes use cases and read models clearer.

Use commands for state-changing user intent:

```text
CreateOrder
CancelOrder
CreateProject
ArchiveProject
ImportProducts
UpdateCustomerProfile
```

Use queries for reads:

```text
GetOrderDetails
ListProjects
SearchProducts
GetCustomerProfile
GetDashboardSummary
```

Rules:

```text
- Commands represent intent.
- Queries do not mutate state.
- Command handlers coordinate state changes.
- Query handlers may use optimized read queries.
- Command handlers should not return large complex read models.
- Query handlers may return read models/DTOs.
- Read models can be eventually consistent only when the business allows it.
- Critical user-visible state must be confirmable from the relational database.
```

Do not introduce command/query folders just for decoration. Use them where they clarify the use case.

---

## 11. EDA Rules

EDA is for facts, async side effects, decoupling, and eventual consistency.

Events describe something that already happened.

Good event names:

```text
OrderPlaced
PaymentCaptured
ProjectArchived
ProductImported
CustomerRegistered
```

Bad event names:

```text
PlaceOrder
CapturePayment
ArchiveProject
ImportProduct
RegisterCustomer
```

The bad names are commands, not events.

Use async messaging for:

```text
- notifications;
- emails;
- search indexing;
- reporting projections;
- external CRM/ERP sync;
- imports/exports;
- non-critical cache warming;
- audit/log side effects when acceptable.
```

Do not use async messaging for:

```text
- core state mutation that must be immediately consistent;
- validating permissions;
- enforcing business invariants;
- changing order/project/task/payment state when the HTTP response depends on it;
- replacing transactions.
```

Reliable event publication rule:

```text
If a domain state change and an async side effect must stay consistent, store the event in a transactional outbox in the same database transaction as the state change.
```

Correct flow:

```text
Application handler
-> domain behavior
-> repository save
-> outbox store
-> DB commit
-> outbox publisher
-> queue/message bus
-> async handler
-> side effect
```

Forbidden flow:

```text
Domain entity -> queue
Controller -> queue for core state change
Application handler -> publish event before DB commit
Async handler -> required for core transaction validity
```

Async handlers must be idempotent because queue delivery is at-least-once.

---

## 12. Transaction Rules

Use synchronous relational database transactions for core state changes.

Transaction required for:

```text
- creating or updating core business entities;
- placing/canceling orders;
- payment state changes;
- project/task state changes;
- account/security-sensitive state changes;
- permission-sensitive mutations;
- storing outbox events with state changes;
- inventory/stock mutations;
- durable audit-critical state.
```

Rules:

```text
- DB transaction must not depend on RabbitMQ availability.
- DB transaction must not depend on Redis availability.
- DB transaction must not depend on OpenSearch availability.
- External side effects should usually happen after commit.
- If an external side effect must be reliable, schedule it through the outbox.
- Do not hide important business mutations in Doctrine listeners.
```

---

## 13. Relational Database Rules

Relational database is the source of truth.

Rules:

```text
- Important invariants should be protected by application/domain rules and durable DB constraints where possible.
- Use migrations for schema changes.
- Do not manually edit production schema.
- Add indexes for real access patterns, not speculation.
- Do not rely on search/cache/queue as canonical storage.
- Repository/query methods must not leak inaccessible data and expect callers to filter it later.
```

---

## 14. Search Rules

OpenSearch / Elasticsearch may be used for:

```text
- full-text search;
- faceted filtering;
- autocomplete;
- read-heavy dashboards;
- denormalized read-side projections.
```

Rules:

```text
- Search index is a read model.
- Search index can lag behind the relational database.
- DB wins when search and DB disagree.
- Reindexing must be possible.
- Indexing consumers must be idempotent.
- Search results must still respect access control.
- Search must not expose data the relational source would deny.
```

Do not write business-critical state only to search.

---

## 15. Redis Rules

Redis may be used for:

```text
- cache;
- rate limiting;
- short-lived counters;
- locks when justified;
- temporary import state;
- session-like transient state when acceptable;
- fast lookup projections that can be rebuilt.
```

Rules:

```text
- Redis is not the source of truth.
- Use TTLs by default.
- Define invalidation strategy for every cache.
- Redis failure should degrade performance, not corrupt core business state.
- Do not store canonical order/product/payment/project state only in Redis.
```

---

## 16. Symfony Rules

Symfony mechanisms are implementation details.

Allowed Symfony tools:

```text
- Controllers as Interfaces adapters;
- Validator for request DTOs and input validation;
- Messenger for command/query dispatch or async messages when wrapped appropriately;
- Doctrine for infrastructure persistence;
- Security for authentication and authorization infrastructure;
- EventDispatcher for framework-level events;
- Console for CLI entry points;
- Cache contracts for cache adapters;
- Workflow component only when it fits the domain model.
```

Rules:

```text
- Symfony must not be the architecture center.
- Controllers stay thin.
- Forms do not own business rules.
- Serializer must not expose domain objects blindly.
- Doctrine entities must not become God objects.
- Doctrine listeners must not hide major business changes.
- Application services should not depend on Symfony Request/Response.
- Domain code should not depend on Symfony components.
```

Default Symfony folder structure can exist, but modular business code should use module/layer structure.

---

## 17. SOLID / Design Principle Rules

Agents and contributors must treat SOLID as production rules, not decorative theory.

### Single Responsibility Principle

A class should have one clear reason to change.

Split code when one class mixes:

```text
- HTTP transport mapping;
- use-case orchestration;
- business rules;
- persistence;
- external integrations;
- message publishing;
- logging;
- API documentation;
- serialization.
```

### Open/Closed Principle

When new cases are expected to grow, prefer extension points:

```text
interfaces
strategies
registries
tagged services
mappers
factories
```

Do not create one shared class that knows every module-specific type.

### Dependency Inversion Principle

Application and Domain depend on abstractions. Infrastructure implements them.

Good:

```text
Application -> PaymentGatewayInterface
Infrastructure -> ExternalPaymentGatewayAdapter
```

Bad:

```text
Application -> ConcreteLiqPayClient
Domain -> Symfony HttpClient
```

### Avoid God classes

Forbidden naming/code smells:

```text
Manager
Helper
Utils
BaseService
AbstractProcessor
CommonService
BigFacade
```

These names are not always illegal, but they require strong justification.

---

## 18. ADR Rule

Any important architectural decision must be documented as an ADR.

ADR required for decisions about:

```text
- module boundaries;
- aggregate ownership;
- source tree structure;
- database schema strategy;
- transaction boundaries;
- outbox/event delivery;
- authorization model;
- cache/search consistency;
- queue usage;
- major Symfony component usage;
- migration from legacy placement to target placement.
```

ADR must include:

```text
- status;
- context;
- decision;
- alternatives considered;
- consequences;
- risks;
- migration/adoption plan when needed.
```

---

## 19. Legacy Migration Classification Rules

When reviewing an existing codebase, classify every placement as one of:

```text
TARGET_RULE
CURRENT_STATE
LEGACY_EXCEPTION
WRONG_AND_MUST_BE_FIXED
```

Do not describe current legacy placement as target architecture.

Example:

```text
src/Application/Admin/Controller
```

Classification:

```text
CURRENT_STATE / LEGACY_EXCEPTION
```

Target:

```text
src/<Module>/Interfaces/Http/Admin
```

Example:

```text
src/Presentation/Controller
```

Classification:

```text
CURRENT_STATE / legacy delivery naming
```

Target:

```text
src/<Module>/Interfaces/Http/Controller
```

Do not move everything in one PR. Migration should be behavior-protected and incremental.

Migration order:

```text
1. Document target architecture.
2. Stop new code from copying legacy placement.
3. Add tests for one risky/important flow.
4. Move one small controller/use case group.
5. Verify routes, responses, and behavior.
6. Repeat by module or flow.
```

---

## 20. AI Agent Rules

AI agents and contributors must not infer target architecture from current legacy folders.

Before making changes, identify:

```text
- current placement;
- target placement;
- whether this is new code or migration;
- whether behavior must be preserved;
- tests needed before change;
- documentation impact.
```

Every architecture-sensitive task must include non-goals.

Common non-goals:

```text
- Do not move all controllers.
- Do not rename namespaces globally.
- Do not change public API contracts.
- Do not change database schema unless explicitly required.
- Do not introduce new packages without justification.
- Do not create generic abstractions before a second real use case exists.
- Do not place business logic in controllers.
```

Final rule:

```text
Current state is evidence, not permission.
Target architecture must be explicit.
```
