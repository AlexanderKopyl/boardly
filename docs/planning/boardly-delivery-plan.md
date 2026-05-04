# Boardly Delivery Plan

Status: living planning document
Last updated: 2026-05-04

## Purpose

This document tracks the next implementation steps for Boardly.

It is not a changelog. It describes planned and in-progress work. Completed changes should be recorded separately in `CHANGELOG.md` once the project starts using release/version notes.

## Current baseline

The first IdentityAccess vertical slice is working end-to-end:

```text
POST /api/auth/register
-> RegisterAccountCommand
-> RegisterAccountHandler
-> database transaction
-> account persistence
-> transactional outbox insert
-> outbox publisher
-> RabbitMQ async transport
-> Messenger consumer
-> AccountRegisteredOutboxMessageHandler
```

Validated locally:

- registration request works without Xdebug timeout;
- Doctrine migrations are up to date;
- outbox record is created after account registration;
- outbox publisher dispatches `AccountRegisteredMessage`;
- RabbitMQ receives the message;
- `messenger:consume async` consumes the message;
- diagnostic `AccountRegisteredOutboxMessageHandler` is executed.

## Planning rules

- Keep vertical slices small and production-shaped.
- Prefer one feature that works end-to-end over many partial abstractions.
- Keep domain logic outside controllers and infrastructure classes.
- Keep application use cases explicit through commands, queries, and handlers.
- Use transactional outbox for reliable domain-event publication.
- Make async consumers idempotent because delivery is at-least-once.
- Do not add generic abstractions before there are at least two real use cases.

## Phase 1: Local development cleanup

Goal: make local development predictable and remove setup ambiguity.

### Tasks

- Normalize `.env` defaults.
- Add `.env.local.example` for local Mac/Homebrew setup.
- Document Docker-oriented environment values separately.
- Document Nginx + PHP-FPM + Xdebug timeout behavior.
- Fix or verify `Makefile` helper commands.
- Add a local infrastructure checklist for PostgreSQL, RabbitMQ, Redis, and OpenSearch.

### Acceptance criteria

- A new developer can configure the project without guessing `DATABASE_URL`.
- CLI and HTTP/PHP-FPM use the same intended environment values.
- Xdebug HTTP debugging does not accidentally look like an application failure.
- Common commands are documented and reproducible.

## Phase 2: Complete authentication flow

Goal: finish the IdentityAccess authentication baseline after registration.

### Tasks

- Implement `POST /api/auth/login`.
- Verify account credentials through the application layer.
- Issue a short-lived JWT access token.
- Issue an opaque refresh token in an HttpOnly Secure cookie.
- Persist refresh token metadata.
- Implement refresh token rotation.
- Implement `POST /api/auth/refresh`.
- Implement `POST /api/auth/logout`.
- Revoke refresh tokens on logout.

### Acceptance criteria

- Registered account can log in.
- Invalid credentials return a controlled API error.
- Access token can authenticate protected requests.
- Refresh token is not exposed in JSON response body.
- Refresh token can be rotated.
- Logout revokes the active refresh token.
- Security-sensitive errors do not leak internal details.

## Phase 3: Current account API

Goal: prove the protected API pipeline end-to-end.

### Tasks

- Implement `GET /api/me`.
- Add Symfony security user resolution for authenticated account identity.
- Return current account profile data.
- Ensure inactive accounts cannot access protected endpoints.

### Acceptance criteria

- Request without access token returns `401`.
- Request with valid access token returns account data.
- Request with expired or invalid token returns `401`.
- Disabled/inactive account access is rejected.

## Phase 4: Testing baseline

Goal: protect the existing IdentityAccess/outbox behavior with automated tests.

### Tasks

- Add tests for `RegisterAccountHandler`.
- Add API tests for registration.
- Add tests for duplicate email rejection.
- Add tests for outbox event creation.
- Add tests for outbox message mapping.
- Add tests for `OutboxPublisher`.
- Add tests for idempotent async consumer behavior.
- Add tests for login and refresh once implemented.

### Acceptance criteria

- Registration behavior is covered by application-level tests.
- HTTP registration behavior is covered by API tests.
- Outbox publishing can be tested without RabbitMQ when possible.
- Consumer idempotency is covered explicitly.
- `composer qa` or equivalent quality command can be used before merging.

## Phase 5: Start core product domain

Goal: begin the Jira-like product functionality with a small vertical slice.

### Initial domain candidate

```text
Workspace / Project / Task
```

Start minimal. Do not model full Jira complexity immediately.

### First candidate endpoints

```text
POST /api/projects
GET /api/projects
POST /api/projects/{projectId}/tasks
GET /api/projects/{projectId}/tasks
```

### Tasks

- Define Project aggregate or entity model.
- Define Task aggregate or entity model.
- Decide the boundary between Project and Task lifecycle.
- Add project creation use case.
- Add task creation use case.
- Add list/read models where needed.
- Add authorization checks for project access.

### Acceptance criteria

- Authenticated user can create a project.
- Authenticated user can list their projects.
- Authenticated user can create a task inside a project.
- Project access is scoped to allowed accounts.
- Domain rules are not implemented directly in controllers.

## Phase 6: Production hardening

Goal: improve reliability, diagnostics, and operational readiness.

### Tasks

- Add structured API error responses consistently.
- Add request correlation ID support.
- Review logging fields for async consumers.
- Add Messenger failure transport operational commands to documentation.
- Add retry/failure handling rules for outbox publisher.
- Add health checks for PostgreSQL, RabbitMQ, Redis, and OpenSearch.
- Add deployment-oriented environment documentation.

### Acceptance criteria

- Failed async messages can be inspected and retried.
- Outbox failures are observable.
- Logs contain enough context for debugging without exposing secrets.
- Local, test, and production environment expectations are documented.

## CHANGELOG policy

Use this plan for future work.

Use `CHANGELOG.md` for completed user-visible or architecture-relevant changes.

Recommended format later:

```markdown
# Changelog

## Unreleased

### Added
- Added login endpoint.

### Changed
- Refactored outbox publishing to dispatch concrete messages.

### Fixed
- Fixed local Xdebug/Nginx timeout documentation.
```

Rules:

- Do not write planned work into changelog as if it is done.
- Keep changelog entries short and factual.
- Update changelog when a feature or meaningful technical change is merged.
- Keep detailed implementation notes in docs, ADRs, or task-specific files.

## Immediate next step

Recommended next implementation task:

```text
Implement POST /api/auth/login with short-lived JWT access token and opaque refresh-token cookie.
```

This completes the next part of the IdentityAccess vertical slice before starting the core Project/Task domain.
