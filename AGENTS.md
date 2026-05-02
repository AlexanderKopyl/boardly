# AGENTS.md

## Purpose

This file is the entrypoint for AI agents working in Boardly.

Do not duplicate architecture rules here. Use this file only as a navigation map and operating guide.

Boardly is a Jira-like project/task/workflow management system. It is not a CRM.

## How to Use This Repository

Before proposing architecture, generating code, or changing structure, read the relevant documentation below.

If a rule is needed, reference the source document instead of restating it in `AGENTS.md`.

Accepted ADRs are binding project decisions. If an ADR conflicts with a generic recommendation, follow the accepted ADR unless the user explicitly asks to replace it with a new ADR.

## Documentation Map

### Architecture Rules

Read:

```text
docs/architecture/project-architecture-rules.md
```

Use this document when working with:

- Hexagonal Architecture;
- DDD;
- EDA;
- CQRS;
- Symfony layer boundaries;
- Redis usage;
- RabbitMQ usage;
- OpenSearch / Elasticsearch usage;
- transaction boundaries;
- source-of-truth decisions;
- module and bounded context design.

If the task touches architecture, this is the primary source.

### Accepted ADRs

Read accepted ADRs before making architecture, structure, module-boundary, event-delivery, API, authentication, account-domain, or infrastructure decisions.

#### ADR-0001: Modular Monolith, Hexagonal Architecture, and DDD

```text
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
```

Use this ADR when working with:

- baseline backend architecture;
- Modular Monolith decisions;
- Hexagonal Architecture layer direction;
- DDD boundaries;
- CQRS usage;
- EDA usage;
- source-of-truth decisions;
- avoiding premature microservices.

#### ADR-0002: Boardly Context-Based Source Structure

```text
docs/adr/0002-use-boardly-context-based-source-structure.md
```

Use this ADR when working with:

- `src/` structure;
- `src/Boardly` product-specific code;
- `src/Boardly/SharedKernel` shared Boardly domain concepts;
- `src/Shared` technical abstractions;
- bounded context folders;
- deciding where new classes should live;
- avoiding empty/decorative modules.

#### ADR-0003: Transactional Outbox for Domain Event Delivery

```text
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

Use this ADR when working with:

- domain event delivery;
- outbox persistence;
- Messenger/RabbitMQ publication;
- async side effects;
- search indexing events;
- audit/activity projections;
- notification events;
- reporting projections;
- event retry and idempotency;
- avoiding direct message dispatch from domain services, entities, or command handlers as part of business mutation.

#### ADR-0004: API-First Symfony Backend with Next.js Frontend

```text
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
```

Use this ADR when working with:

- API-first product direction;
- Symfony backend responsibilities;
- Next.js frontend responsibilities;
- controller/API adapter design;
- avoiding Twig as the main product UI;
- API response DTOs and read models;
- frontend/backend repository structure decisions.

#### ADR-0005: JWT Access Tokens and HttpOnly Refresh Cookies

```text
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
```

Use this ADR when working with:

- authentication strategy;
- JWT access tokens;
- refresh token cookies;
- token rotation;
- refresh token reuse detection;
- CORS and CSRF baseline;
- IdentityAccess boundaries;
- account lifecycle;
- admin-approved accounts;
- frontend token storage behavior.

### Design Documents

#### ChangeIssueStatus

```text
docs/design/change-issue-status.md
```

Use this document when working with:

- the `ChangeIssueStatus` use case;
- Issue status ownership;
- Workflow transition validation boundary;
- permission check boundary;
- transaction boundary for status changes;
- `IssueStatusChanged` domain event shape;
- async side effects after status change;
- failure cases for issue status changes.

#### Authentication API Strategy

```text
docs/design/authentication-api-strategy.md
```

Use this document when working with:

- register/login/refresh/logout/me API contracts;
- IdentityAccess application use cases;
- Account lifecycle;
- admin approval routes;
- first system admin bootstrap;
- authentication error responses;
- password hashing boundary;
- login rate limiting;
- refresh token persistence;
- frontend authentication behavior;
- CORS/CSRF implementation details.

#### Account Domain Model

```text
docs/design/account-domain-model.md
```

Use this document when working with:

- `Account` aggregate boundary;
- account value objects;
- account lifecycle states and transitions;
- system-admin capability and safety rules;
- account persistence fields;
- `AccountRepositoryInterface`;
- `RefreshSession` model;
- refresh token family/reuse detection;
- login/refresh/logout application flows;
- IdentityAccess domain events;
- account-domain error model;
- implementation checklist and anti-patterns.

### Subagents Map

Read:

```text
docs/agents/subagents-map.md
```

Use this document when deciding which specialized subagent should handle the task.

It defines routing for:

- Product & Domain Analyst;
- DDD / Domain Modeling;
- Workflow & Permissions;
- Symfony Architecture;
- Async / Messaging;
- Search & Read Models;
- Cache & Performance;
- Testing & QA;
- Security;
- DevOps / Observability;
- ADR Writer.

If a task crosses several responsibilities, use the multi-subagent routing rules from this document.

### ADR Template

Read:

```text
docs/adr/0000-template.md
```

Use this template when creating Architecture Decision Records.

Create ADRs for decisions about:

- module boundaries;
- aggregate ownership;
- workflow architecture;
- authorization model;
- authentication strategy;
- token/session strategy;
- transaction boundaries;
- event contracts;
- Outbox usage;
- search consistency;
- cache strategy;
- major Symfony component usage;
- major infrastructure choices.

Accepted ADR naming format:

```text
docs/adr/0001-short-decision-title.md
```

## Agent Routing Guide

Use the documentation like this:

| Task type | Read first |
|---|---|
| Choosing a specialized subagent | `docs/agents/subagents-map.md` |
| Architecture proposal | `docs/architecture/project-architecture-rules.md`, then accepted ADRs |
| Symfony structure proposal | `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`, `docs/adr/0002-use-boardly-context-based-source-structure.md` |
| Source code placement decision | `docs/adr/0002-use-boardly-context-based-source-structure.md` |
| Creating a new Boardly module/context | `docs/adr/0002-use-boardly-context-based-source-structure.md`, then `docs/architecture/project-architecture-rules.md` |
| API/backend/frontend boundary | `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` |
| API response design | `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`, then relevant design document |
| Authentication strategy | `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`, then `docs/design/authentication-api-strategy.md` |
| IdentityAccess design or implementation | `docs/design/authentication-api-strategy.md`, `docs/design/account-domain-model.md`, then ADR-0001, ADR-0002, ADR-0004, and ADR-0005 |
| Account domain model | `docs/design/account-domain-model.md`, then `docs/design/authentication-api-strategy.md` and `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` |
| RefreshSession / refresh token rotation | `docs/design/account-domain-model.md`, then `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` |
| Register/login/refresh/logout/me API | `docs/design/authentication-api-strategy.md`, `docs/design/account-domain-model.md`, then `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` |
| Admin account approval | `docs/design/authentication-api-strategy.md`, `docs/design/account-domain-model.md`, then `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` |
| Security-sensitive API work | `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`, `docs/design/authentication-api-strategy.md`, `docs/design/account-domain-model.md`, and `docs/agents/subagents-map.md` |
| Domain modeling | `docs/architecture/project-architecture-rules.md`, `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`, and `docs/agents/subagents-map.md` |
| Workflow design | `docs/architecture/project-architecture-rules.md`, `docs/design/change-issue-status.md`, and `docs/agents/subagents-map.md` |
| CQRS command/query design | `docs/architecture/project-architecture-rules.md` and `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md` |
| RabbitMQ / async flow | `docs/architecture/project-architecture-rules.md`, `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, and `docs/agents/subagents-map.md` |
| Domain event delivery / Outbox | `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, then `docs/architecture/project-architecture-rules.md` |
| Redis cache design | `docs/architecture/project-architecture-rules.md` and `docs/agents/subagents-map.md` |
| OpenSearch / Elasticsearch design | `docs/architecture/project-architecture-rules.md`, `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, and `docs/agents/subagents-map.md` |
| ChangeIssueStatus design or implementation | `docs/design/change-issue-status.md`, then ADR-0001, ADR-0002, and ADR-0003 |
| Important architecture decision | Existing accepted ADRs, then `docs/adr/0000-template.md` |
| Writing an ADR | Existing accepted ADRs, then `docs/adr/0000-template.md` |

## Before Changing Code

Before creating or modifying code, check:

1. Does the task require reading the architecture rules?
2. Does the task require reading accepted ADRs?
3. Does the task require reading a scenario design document?
4. Does the task require choosing a specialized subagent?
5. Does the task require a new ADR?
6. Is the change consistent with the existing documentation?
7. Is the requested change code, documentation, or architecture design?

If documentation and user request conflict, explain the conflict before changing code.

## Source Structure Rules for Agents

For source code placement, follow:

```text
docs/adr/0002-use-boardly-context-based-source-structure.md
```

Default classification:

```text
src/Boardly              -> product-specific Boardly code
src/Boardly/SharedKernel -> small shared Boardly domain concepts
src/Shared               -> technical abstractions that do not know about Boardly
```

Do not create future modules just because they are listed as candidates.

Create modules only when a real use case requires them.

## API-First Rules for Agents

For the backend/frontend boundary, follow:

```text
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
```

Default rule:

```text
Next.js frontend -> Symfony HTTP API -> Application layer -> Domain layer
```

Controllers are delivery adapters. They must stay thin.

API responses must not expose Doctrine entities directly.

Use explicit API DTOs or read models for responses.

Do not use Twig as the main product UI unless a future ADR changes the product direction.

## Authentication and Account Rules for Agents

For authentication and IdentityAccess, follow:

```text
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
docs/design/authentication-api-strategy.md
docs/design/account-domain-model.md
```

Default authentication model:

```text
short-lived JWT access token
+
opaque HttpOnly Secure refresh token cookie
```

IdentityAccess owns authentication, account lifecycle, credentials, global system-admin capability, and refresh-session lifecycle:

```text
src/Boardly/IdentityAccess/
```

Primary domain entity name:

```text
Account
```

Initial account statuses:

```text
pending_approval
active
rejected
disabled
```

Account owns only:

```text
identity + lifecycle + credentials + global system-admin capability
```

Account must not own:

```text
project permissions
project memberships
workflow permissions
issue permissions
refresh sessions
frontend/session state
```

RefreshSession is separate from Account and owns refresh-token/session lifecycle.

Only active accounts may receive access tokens or refresh cookies.

Do not store access tokens in browser persistent storage.

Do not return refresh tokens in JSON.

Do not store raw refresh tokens.

Persist only refresh token hashes.

Do not put dynamic project/workflow/issue permissions into JWT claims.

Do not implement public self-service password reset in the first auth milestone.

Password reset is admin-controlled future scope unless a later ADR/design document changes this.

## Event Delivery Rules for Agents

For reliable domain event delivery, follow:

```text
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

Default rule:

```text
Business state change + outbox event record must be committed in the same DB transaction.
```

Do not dispatch Messenger/RabbitMQ messages directly from:

- domain entities;
- domain services;
- command handlers as part of the business mutation.

Use the outbox for durable domain event delivery when async side effects must reliably follow committed business state.

Async consumers must be idempotent.

## Default Architecture Response Format

When proposing architecture, use the response format defined in:

```text
docs/architecture/project-architecture-rules.md
```

Do not redefine that format here.

## First Deep Design Scenario

For initial architecture validation, use:

```text
ChangeIssueStatus
```

The scenario design is documented in:

```text
docs/design/change-issue-status.md
```

Details and constraints are also documented in:

```text
docs/architecture/project-architecture-rules.md
```

Subagent routing for this scenario is documented in:

```text
docs/agents/subagents-map.md
```

When implementing or changing this scenario, read:

```text
docs/design/change-issue-status.md
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

## Authentication and Account Design Scenario

For authentication and IdentityAccess implementation, use:

```text
Authentication API Strategy
Account Domain Model
```

The scenario designs are documented in:

```text
docs/design/authentication-api-strategy.md
docs/design/account-domain-model.md
```

The binding architecture decision is documented in:

```text
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
```

When implementing or changing this scenario, read:

```text
docs/design/authentication-api-strategy.md
docs/design/account-domain-model.md
docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md
docs/adr/0002-use-boardly-context-based-source-structure.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
```
