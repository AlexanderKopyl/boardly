# Boardly Architecture Rules

## 1. Purpose

This document defines baseline architecture rules for Boardly.

Boardly is a Jira-like project and task management system. It is not a CRM.

The architecture must support:

- projects;
- issues / tasks;
- workflows;
- boards;
- permissions;
- collaboration;
- notifications;
- audit;
- search;
- reporting;
- automation.

The goal is a practical modular system, not framework-driven code generation.

---

## 2. Core Architecture

Default architecture:

- Symfony backend;
- Modular Monolith;
- DDD;
- Hexagonal Architecture;
- CQRS where it simplifies use cases and read models;
- EDA where async side effects are justified.

Infrastructure:

- relational database as source of truth;
- RabbitMQ for async side effects;
- Redis for cache / fast storage;
- OpenSearch / Elasticsearch for search and read-side queries.

Hard rules:

- DB is the source of truth.
- Redis is not the source of truth.
- OpenSearch / Elasticsearch is not the source of truth.
- RabbitMQ must not be required for core state changes to be valid.
- Symfony is an implementation framework, not the architecture center.

---

## 3. Hexagonal Architecture Rules

Use Ports & Adapters.

Main direction:

```text
Interfaces / UI -> Application -> Domain
Infrastructure -> Application / Domain ports
```

Forbidden direction:

```text
Domain -> Symfony
Domain -> Doctrine
Domain -> Messenger
Domain -> Redis
Domain -> OpenSearch
Domain -> RabbitMQ
```

### Domain Layer

Owns business rules and invariants.

May contain:

- aggregates;
- entities;
- value objects;
- domain services;
- domain events;
- repository interfaces if they express domain needs;
- specifications / policies.

Must not contain:

- Symfony attributes;
- Doctrine ORM mapping if avoidable;
- Messenger messages tied to transport;
- HTTP requests / responses;
- Redis clients;
- OpenSearch clients;
- RabbitMQ clients.

### Application Layer

Owns use case orchestration.

May contain:

- commands;
- command handlers;
- queries;
- query handlers;
- application services;
- ports;
- DTOs for use cases;
- transaction boundary coordination.

Must not own core business invariants.

Application handlers should coordinate:

1. permission checks;
2. loading aggregates;
3. calling domain behavior;
4. persisting changes;
5. publishing domain events or scheduling async side effects.

### Infrastructure Layer

Implements adapters.

May contain:

- Doctrine repositories;
- DBAL queries;
- Messenger adapters;
- Redis adapters;
- OpenSearch adapters;
- file storage adapters;
- external API clients;
- Symfony-specific service wiring.

Infrastructure depends inward. Domain does not depend on infrastructure.

### Interfaces / UI Layer

Delivers input and output.

May contain:

- HTTP controllers;
- API request DTOs;
- response presenters;
- CLI commands;
- webhook controllers;
- admin UI adapters.

Controllers must stay thin.

Controller responsibilities:

1. accept request;
2. validate transport-level input;
3. call application command/query bus;
4. return response.

Controllers must not contain workflow rules, permission logic, or aggregate mutation logic.

---

## 4. DDD Rules

Use DDD to model business behavior, not to create decorative folders.

Important bounded context candidates:

- Identity & Access;
- Projects;
- Issues;
- Workflow;
- Boards;
- Collaboration;
- Attachments;
- Notifications;
- Search;
- Reporting;
- Audit;
- Integrations;
- Automation.

Important aggregate candidates:

- Project;
- Issue;
- Workflow;
- Board;
- Team;
- CommentThread if collaboration becomes complex;
- NotificationPreference if notification rules become complex.

Rules:

- Aggregates protect invariants.
- Not every entity is an aggregate.
- Value objects represent meaningful domain concepts.
- Domain services are used only when behavior does not naturally belong to one aggregate.
- Repositories should express collection-like access to aggregates.
- Avoid generic `Manager`, `Helper`, `BaseService`, and `AbstractService` classes.

Example:

`Issue` should own lifecycle behavior such as assignment, priority changes, and status transition execution.

Workflow validation may be delegated to a policy/domain service if it depends on workflow configuration and permissions.

---

## 5. EDA Rules

Use Event-Driven Architecture for side effects and decoupling, not for every method call.

Event types:

- Domain Event: internal business fact.
- Integration Event: stable contract for external consumers or cross-boundary integrations.

Domain event examples:

- IssueCreated;
- IssueAssigned;
- IssueStatusChanged;
- IssueCommented;
- ProjectArchived;
- WorkflowTransitionChanged.

Integration event examples:

- IssueCreatedV1;
- IssueStatusChangedV1;
- IssueCommentAddedV1;
- ProjectArchivedV1.

Rules:

- Domain events describe something that already happened.
- Events are not commands.
- Async handlers must be idempotent.
- Message contracts should be versioned when exposed outside the module boundary.
- Use Outbox Pattern when reliable event publishing matters.
- Failed async side effects must not invalidate already committed core state.

Use async messaging for:

- notifications;
- search indexing;
- audit side effects when acceptable;
- reporting projections;
- external integrations;
- imports / exports;
- reminders;
- attachment processing.

Do not use async messaging for:

- validating workflow transitions;
- changing issue status;
- enforcing permissions;
- core state mutation that must be immediately consistent.

---

## 6. CQRS Rules

CQRS is allowed when it makes the model clearer.

Use commands for state changes:

- CreateProject;
- CreateIssue;
- AssignIssue;
- ChangeIssueStatus;
- CommentOnIssue;
- MoveIssueOnBoard.

Use queries for reads:

- GetIssueDetails;
- SearchIssues;
- GetProjectBoard;
- GetUserAssignedIssues;
- GetOverdueIssues;
- GetAuditTrail.

Rules:

- Commands represent user intent.
- Queries do not mutate state.
- Command handlers should not return complex read models.
- Query handlers may use optimized DBAL, Doctrine projections, or OpenSearch where appropriate.
- Read models can be eventually consistent if business allows it.
- Critical user-visible state should be confirmable from the database.

---

## 7. Transaction Rules

Synchronous DB transaction is required for:

- creating project;
- archiving project;
- creating issue;
- assigning issue;
- changing issue status;
- changing priority;
- validating workflow transition;
- permission-sensitive mutations;
- storing audit-critical state.

Can be asynchronous:

- sending notifications;
- updating search index;
- updating reporting projections;
- syncing external systems;
- generating attachment previews;
- recalculating non-critical counters.

A successful DB transaction must not depend on RabbitMQ, Redis, or OpenSearch availability.

---

## 8. Search Rules

OpenSearch / Elasticsearch is used for:

- full-text issue search;
- project search;
- comment search;
- filters and autocomplete;
- heavy read-side search queries;
- dashboard projections if justified.

Rules:

- Search index can lag behind DB.
- DB wins if search result conflicts with DB state.
- Reindexing must be possible.
- Indexing consumers must be idempotent.
- Search documents are read models, not domain objects.

---

## 9. Redis Rules

Redis may be used for:

- permission cache;
- short-lived counters;
- board/project config cache;
- rate limiting;
- temporary import state;
- distributed locks when justified;
- user presence if needed.

Rules:

- Use TTLs by default.
- Define invalidation strategy for every cache.
- Do not store canonical project or issue state only in Redis.
- Cache failure should degrade performance, not break core business operations.

---

## 10. Symfony Rules

Symfony mechanisms are implementation details.

Allowed Symfony tools:

- Messenger for command/query dispatch and async messages;
- Doctrine for persistence adapters;
- Security Voters / Access Decision Manager for authorization;
- Validator for input and DTO validation;
- Event Dispatcher for framework-level events;
- Console Commands for workers, imports, reindexing, maintenance;
- Cache Contracts for cache adapters;
- Lock Component for justified concurrency control;
- Workflow Component only if it fits the domain model.

Rules:

- Controllers are thin.
- Forms do not own business logic.
- Doctrine listeners must not hide important business mutations.
- Security must not be only route-level.
- Serializer must not expose domain objects blindly.
- Symfony Workflow Component must not force an anemic domain model.

---

## 11. Recommended Initial Feature Flow

Use `ChangeIssueStatus` as the first deep architecture scenario.

Expected flow:

1. Controller receives request.
2. Application command is created.
3. Permission policy checks actor access.
4. Issue aggregate is loaded.
5. Workflow configuration is loaded.
6. Workflow policy validates transition.
7. Issue executes status transition.
8. Domain event `IssueStatusChanged` is recorded.
9. Issue is persisted in one DB transaction.
10. Async side effects are scheduled after commit.
11. Search index, notifications, reporting, and activity projections are updated asynchronously.

Forbidden flow:

1. Controller changes `status` field directly.
2. Frontend decides allowed transitions.
3. Doctrine listener secretly sends core business messages.
4. OpenSearch is updated before DB commit.
5. RabbitMQ success is required for status change to be valid.

---

## 12. ADR Rule

Any important architectural decision must be documented as an ADR.

Use:

```text
docs/adr/0000-template.md
```

Create accepted ADRs as:

```text
docs/adr/0001-short-decision-title.md
```

ADR is required for decisions about:

- module boundaries;
- aggregate ownership;
- transaction boundaries;
- event contracts;
- Outbox usage;
- search consistency;
- cache strategy;
- workflow architecture;
- authorization model;
- major Symfony component usage;
- replacing modular monolith with services.
