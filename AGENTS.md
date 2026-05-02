# AGENTS.md

## Purpose

This file gives AI agents the minimum context needed to work safely in Boardly.

Boardly is a Jira-like project and task management system.
It is not a CRM.

The project is designed around:

- Symfony;
- Modular Monolith;
- DDD;
- Hexagonal Architecture;
- CQRS where useful;
- EDA where justified;
- RabbitMQ;
- Redis;
- OpenSearch / Elasticsearch;
- relational database as source of truth.

## Documentation Map

Start here:

1. `docs/architecture/project-architecture-rules.md`
   - Core architecture rules.
   - Hexagonal Architecture rules.
   - DDD, EDA, CQRS rules.
   - Symfony implementation boundaries.

2. `docs/adr/0000-template.md`
   - Template for Architecture Decision Records.
   - Use it for important architecture decisions.

## Global Rules

Agents must follow these rules:

- Business behavior first, framework mechanics second.
- Modular monolith by default.
- No microservices unless an ADR explicitly accepts that decision.
- DB is the source of truth.
- Redis is cache / fast storage only.
- OpenSearch / Elasticsearch is search and read-side only.
- RabbitMQ is for async side effects, not core consistency.
- Symfony is an implementation framework, not the architecture center.

## Layer Rules

Use Hexagonal Architecture.

Allowed dependency direction:

```text
Interfaces / UI -> Application -> Domain
Infrastructure -> Application / Domain ports
```

Forbidden dependency direction:

```text
Domain -> Symfony
Domain -> Doctrine
Domain -> Messenger
Domain -> Redis
Domain -> OpenSearch
Domain -> RabbitMQ
```

## Domain Rules

Domain layer may contain:

- aggregates;
- entities;
- value objects;
- domain services;
- domain events;
- policies/specifications;
- repository interfaces if they express domain needs.

Domain layer must not contain:

- controllers;
- HTTP requests/responses;
- Doctrine repositories;
- Redis clients;
- OpenSearch clients;
- RabbitMQ clients;
- Symfony-specific logic.

## Application Rules

Application layer owns use case orchestration.

It may contain:

- commands;
- command handlers;
- queries;
- query handlers;
- application services;
- ports;
- transaction coordination.

Application layer must not hide core business invariants.

## Infrastructure Rules

Infrastructure implements adapters.

It may contain:

- Doctrine repositories;
- DBAL queries;
- Messenger handlers/adapters;
- Redis adapters;
- OpenSearch adapters;
- file storage adapters;
- external API clients.

Infrastructure must depend inward.

## Interfaces / UI Rules

Interfaces layer may contain:

- HTTP controllers;
- API request DTOs;
- response presenters;
- CLI commands;
- webhook controllers.

Controllers must stay thin:

1. accept request;
2. validate transport input;
3. call application command/query;
4. return response.

Controllers must not contain workflow rules, permission rules, or aggregate mutation logic.

## DDD Rules

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
- Team.

Do not create generic `Manager`, `Helper`, `BaseService`, or `AbstractService` classes without strong business meaning.

## CQRS Rules

Commands mutate state and represent user intent.

Examples:

- CreateProject;
- CreateIssue;
- AssignIssue;
- ChangeIssueStatus;
- CommentOnIssue.

Queries read state and must not mutate anything.

Examples:

- GetIssueDetails;
- SearchIssues;
- GetProjectBoard;
- GetUserAssignedIssues;
- GetAuditTrail.

## EDA Rules

Domain events describe business facts that already happened.

Examples:

- IssueCreated;
- IssueAssigned;
- IssueStatusChanged;
- IssueCommented;
- ProjectArchived.

Async handlers must be idempotent.
Use Outbox Pattern when reliable publishing matters.

Do not use RabbitMQ for core state changes like changing issue status or enforcing permissions.

## First Architecture Flow

Use `ChangeIssueStatus` as the first deep design scenario.

It must cover:

- permission check;
- workflow validation;
- issue aggregate mutation;
- DB transaction;
- domain event;
- async notifications;
- search indexing;
- reporting projection;
- audit behavior.

## ADR Rules

Create an ADR for important decisions about:

- module boundaries;
- aggregate ownership;
- workflow architecture;
- authorization model;
- transaction boundaries;
- event contracts;
- Outbox usage;
- search consistency;
- cache strategy;
- major Symfony component usage.

Use:

```text
docs/adr/0000-template.md
```

Accepted ADR naming:

```text
docs/adr/0001-short-decision-title.md
```

## Before Changing Code

Before generating or changing code, agents must check:

1. Does this respect Hexagonal Architecture?
2. Is business logic placed outside controllers?
3. Are domain invariants protected by aggregates/policies?
4. Is DB still the source of truth?
5. Are async side effects idempotent?
6. Is an ADR needed?

## Default Answer Style

When proposing architecture, answer with:

1. Summary
2. Business context
3. Proposed architecture
4. Main components
5. Commands / queries / events
6. Consistency and transactions
7. Search / cache / async notes
8. Patterns used
9. Symfony implementation direction
10. Risks and trade-offs
11. MVP recommendation
