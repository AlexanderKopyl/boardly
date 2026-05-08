# ADR-0006: Use Frontend Context-Based Hexagonal Architecture

## Status

Accepted

## Date

2026-05-08

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Frontend / product UI owner
- Backend owner: Backend / Symfony owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

ADR-0004 established Boardly as an API-first product with a Symfony backend and a Next.js frontend.

ADR-0005 established the authentication model based on short-lived JWT access tokens and opaque HttpOnly refresh cookies.

The frontend now needs an explicit architecture rule before implementation starts.

The risk is that the frontend grows into a flat structure such as:

```text
components/
services/
utils/
hooks/
```

This would make authentication, permissions, project screens, issue screens, board behavior, workflow UI, and API integration hard to reason about.

Boardly backend already follows Modular Monolith, Hexagonal Architecture, and DDD. The frontend should use a compatible mental model without copying backend DDD blindly.

The frontend must stay clear about one important boundary:

```text
The backend remains the source of truth for business invariants.
```

The frontend may model UI state and API contracts, but it must not become authoritative for permissions, workflow transitions, identity lifecycle, persistence, or project/task state.

## Decision

We will organize the frontend around context-based modules with frontend hexagonal layers.

The primary frontend source structure will be:

```text
frontend/src/
  app/
  shared/
  contexts/
    <context-name>/
      domain/
      application/
      infrastructure/
      presentation/
```

The first context will be:

```text
frontend/src/contexts/identity-access/
```

The initial IdentityAccess frontend structure will be:

```text
frontend/src/contexts/identity-access/
  domain/
    account.ts
    account-status.ts
    auth-session.ts
    auth-error.ts

  application/
    ports/
      auth-gateway.ts
      auth-session-store.ts
    use-cases/
      login.ts
      register.ts
      refresh-session.ts
      logout.ts
      bootstrap-session.ts

  infrastructure/
    http/
      auth-http-gateway.ts
      auth-api-contracts.ts
    state/
      auth-memory-store.ts

  presentation/
    ui/
      LoginForm.tsx
      RegisterForm.tsx
      LogoutButton.tsx
    guards/
      ProtectedRoute.tsx
    hooks/
      useAuth.ts
```

Future frontend contexts may include:

```text
frontend/src/contexts/projects/
frontend/src/contexts/issues/
frontend/src/contexts/boards/
frontend/src/contexts/workflow/
frontend/src/contexts/notifications/
frontend/src/contexts/reporting/
```

## Scope

This decision applies to:

- frontend source organization;
- Next.js application structure;
- frontend authentication flow;
- frontend API integration;
- frontend module boundaries;
- UI state and use-case orchestration;
- future project, issue, board, workflow, notification, and reporting screens.

This decision does not apply to:

- backend bounded-context structure;
- backend domain model ownership;
- backend persistence mapping;
- backend command/query handlers;
- backend event architecture;
- final component library choice;
- final data-fetching library choice;
- final visual design system.

## Architectural Rules

- Frontend code must be organized by business/product context, not by technical buckets only.
- `frontend/src/app/` is for Next.js routing, layouts, and page composition.
- `frontend/src/shared/` is only for truly shared frontend infrastructure and UI primitives.
- `frontend/src/contexts/` contains product contexts.
- Each context may contain `domain`, `application`, `infrastructure`, and `presentation` layers.
- Context domain models are frontend models, not backend Doctrine entities and not backend aggregates.
- Frontend domain models may represent API contracts, UI state, and screen-level concepts.
- Frontend domain models must not duplicate backend business invariants as the source of truth.
- Application use cases orchestrate frontend flows.
- Application use cases depend on ports, not on concrete HTTP clients, React components, or browser storage.
- Infrastructure adapters implement application ports.
- Presentation contains React components, hooks, guards, forms, and UI-specific composition.
- Presentation must not contain token refresh algorithms, backend URL construction, or API error normalization.
- Raw `fetch` or Axios calls must not be scattered inside React components.
- Authentication refresh-token behavior must remain hidden behind the auth application/infrastructure boundary.
- Access token must remain memory-only.
- Refresh token must never be readable by JavaScript.
- Frontend must not authoritatively decide permissions, workflow transitions, or account access.
- Backend responses and stable error codes should drive UI behavior where business decisions are involved.

## Layer Responsibilities

### app

`app` owns Next.js routing and composition only.

Allowed examples:

```text
frontend/src/app/layout.tsx
frontend/src/app/providers.tsx
frontend/src/app/login/page.tsx
frontend/src/app/register/page.tsx
frontend/src/app/dashboard/page.tsx
```

Rules:

- pages compose context presentation components;
- pages do not build raw API requests;
- pages do not normalize backend errors;
- pages do not own token refresh behavior.

### shared

`shared` owns cross-context primitives.

Allowed examples:

```text
frontend/src/shared/config/env.ts
frontend/src/shared/api/http-client.ts
frontend/src/shared/api/api-error.ts
frontend/src/shared/ui/Button.tsx
frontend/src/shared/ui/Input.tsx
```

Rules:

- shared code must not depend on a product context;
- shared UI components must stay generic;
- shared API infrastructure may know HTTP mechanics, but not product use cases.

### contexts

`contexts` owns product-specific frontend modules.

Each context should be internally cohesive and externally explicit.

A context should not import random implementation details from another context.

If cross-context communication is needed, prefer explicit public exports or backend-provided read models instead of deep imports.

### domain

Frontend `domain` contains frontend-level types and small pure rules.

Allowed examples:

```text
Account
AccountStatus
AuthSession
AuthErrorCode
IssueSummary
ProjectListItem
BoardColumnView
```

Rules:

- no React imports;
- no Next.js imports;
- no HTTP client imports;
- no browser storage access;
- no backend persistence assumptions;
- no authoritative business rules that belong to Symfony.

### application

Frontend `application` contains frontend use cases and ports.

Allowed examples:

```text
login
register
refreshSession
logout
bootstrapSession
loadProjectBoard
changeVisibleFilter
```

Rules:

- use cases orchestrate frontend flows;
- use cases depend on ports/interfaces;
- use cases do not import React components;
- use cases do not call raw `fetch` directly;
- use cases do not read `localStorage`, cookies, or environment variables directly.

### infrastructure

Frontend `infrastructure` contains adapters.

Allowed examples:

```text
auth-http-gateway.ts
auth-memory-store.ts
project-http-gateway.ts
```

Rules:

- HTTP adapters translate between backend API contracts and frontend domain/application models;
- state adapters implement memory-only session storage where required;
- infrastructure may depend on `shared/api` and `shared/config`;
- infrastructure must hide transport details from presentation.

### presentation

Frontend `presentation` contains UI and React integration.

Allowed examples:

```text
LoginForm.tsx
RegisterForm.tsx
ProtectedRoute.tsx
useAuth.ts
ProjectSidebar.tsx
IssueDetailsPanel.tsx
```

Rules:

- presentation calls application use cases or context hooks;
- presentation renders UI state;
- presentation handles form state and display-level validation;
- presentation does not own backend business decisions;
- presentation does not contain token refresh/retry algorithms.

## Initial Implementation Direction

Implementation must start with the IdentityAccess frontend context.

Initial milestone:

1. Create minimal Next.js + TypeScript skeleton under `frontend/`.
2. Add frontend environment config.
3. Add shared HTTP client and normalized API errors.
4. Add IdentityAccess domain models.
5. Add IdentityAccess application ports.
6. Add IdentityAccess HTTP gateway.
7. Add memory-only auth session store.
8. Add login and registration use cases.
9. Add refresh-on-bootstrap use case.
10. Add logout use case.
11. Add protected route guard.
12. Add basic authenticated dashboard placeholder.

Project, issue, board, workflow, and reporting UI must not start before the authentication flow is stable.

## Authentication-Specific Rules

The frontend auth model follows ADR-0005.

Access token:

```text
- returned in JSON from login and refresh
- stored in memory only
- sent as Authorization: Bearer <token>
```

Refresh token:

```text
- opaque
- stored in HttpOnly cookie
- managed by browser
- never returned in JSON
- never readable by JavaScript
```

All auth/cookie-dependent requests must include credentials.

For fetch:

```ts
fetch(url, {
  credentials: 'include',
});
```

Forbidden storage locations:

```text
localStorage
sessionStorage
IndexedDB
JavaScript-readable cookies
URL params
persisted Zustand/Redux storage
NEXT_PUBLIC_* environment variables
```

## Alternatives Considered

### Option A: Flat technical folders

Example:

```text
components/
services/
utils/
hooks/
types/
```

Pros:

- simple at the very beginning;
- familiar to many frontend developers;
- low upfront structure.

Cons:

- weak module boundaries;
- auth logic tends to spread across components, hooks, and services;
- product contexts become hard to isolate;
- large files such as `api.ts`, `auth.ts`, or `utils.ts` are likely;
- does not match Boardly backend architecture thinking.

Rejected because Boardly is expected to grow into a complex product UI.

### Option B: Pure Feature-Sliced Design

Example:

```text
app/
pages/
widgets/
features/
entities/
shared/
```

Pros:

- known frontend modular architecture;
- good for large React applications;
- separates UI composition from features and entities.

Cons:

- introduces terminology that does not map directly to Boardly backend context structure;
- may be overkill before real project/issue/board UI exists;
- can cause confusion between frontend entities and backend DDD entities.

Not adopted as the primary structure now.

Some FSD ideas may still be used later where useful, but the primary Boardly frontend structure is context-based hexagonal architecture.

### Option C: Context-based frontend hexagonal architecture

Pros:

- aligns with Boardly backend architectural thinking;
- keeps product contexts explicit;
- separates UI, use cases, ports, adapters, and frontend models;
- prevents raw API calls from spreading through components;
- makes auth flow easier to test;
- allows future contexts to grow independently.

Cons:

- more files than a small flat React app;
- requires discipline in imports and boundaries;
- can feel heavy if used for trivial UI pieces.

Accepted because Boardly is a product system, not a small landing page or CRUD demo.

## Consequences

### Positive

- Frontend module boundaries become explicit early.
- Authentication flow can be implemented as a coherent vertical slice.
- API integration stays centralized behind gateways and ports.
- Presentation components remain simpler and easier to test.
- Future contexts such as Projects, Issues, Boards, Workflow, and Notifications can be added without flattening the codebase.
- The frontend architecture matches the backend architecture mental model without moving backend business ownership into the browser.

### Negative

- More boilerplate than a basic Next.js app.
- Developers must understand layer boundaries.
- Small features may initially look more complex than necessary.
- Incorrect use may create fake domain layers with no real value.

### Neutral / Operational

- The initial frontend skeleton should include only the folders needed for the first auth slice.
- Empty contexts should not be created before they are needed.
- Contexts should expose stable public APIs when cross-context usage appears.
- Import rules may later be enforced with ESLint boundaries.

## Trade-offs

- We accept more structure in exchange for long-term maintainability.
- We accept more files in exchange for clearer responsibility boundaries.
- We align frontend architecture with backend architecture, but we do not copy backend DDD semantics blindly.
- We keep business truth in Symfony and use frontend domain models only for UI/application modeling.
- We defer full FSD adoption until the product UI grows enough to justify it.

## Impact on Layers

### Domain

- No backend domain ownership moves to the frontend.
- Frontend domain models represent frontend state, API contracts, and UI concepts.
- Backend remains authoritative for business invariants.

### Application

- Frontend use cases orchestrate browser-side flows.
- Use cases depend on ports and are testable without HTTP or React.
- Authentication bootstrap, login, logout, and refresh become explicit application operations.

### Infrastructure

- HTTP gateways implement backend API communication.
- State adapters implement memory-only auth session storage.
- Shared HTTP client handles credentials, authorization header, error normalization, refresh retry, and JSON parsing.

### Interfaces / UI

- React components and hooks live in presentation.
- Next.js app routes compose context presentation components.
- Components remain focused on rendering, user interaction, and form state.

## Impact on DDD

This ADR introduces a frontend-compatible DDD-inspired structure.

Rules:

- frontend contexts are inspired by bounded contexts;
- frontend domain models are not backend aggregates;
- frontend must not duplicate backend policies as authoritative rules;
- frontend may represent read models and view models returned by backend APIs;
- backend still validates every command.

Example:

```text
The frontend may render available workflow actions returned by the API.
The frontend must not decide that an issue status transition is allowed.
Symfony must validate the transition again when the command is submitted.
```

## Impact on EDA

This ADR does not introduce frontend event-driven architecture by itself.

Future real-time UI updates may use polling, Server-Sent Events, WebSockets, Mercure, or another mechanism.

If introduced later, frontend events must represent UI/application events or backend facts, not replace backend domain events.

## Impact on CQRS

This ADR fits the existing API-first and CQRS direction.

Frontend contexts may consume query/read-model APIs optimized for screens.

Frontend commands should call backend use-case-oriented endpoints instead of mutating local state as if it were the source of truth.

Read models may be mapped in infrastructure adapters before being used by presentation components.

## Symfony Implementation Direction

Symfony remains the backend API owner.

This ADR does not change Symfony source structure.

Backend responsibilities remain:

- authentication truth;
- authorization decisions;
- workflow validation;
- project/task state;
- persistence;
- domain events;
- command/query handling;
- API contracts.

The frontend must integrate with Symfony through explicit API contracts.

## Frontend Implementation Direction

Use Next.js with TypeScript.

Initial direction:

```text
frontend/
  package.json
  next.config.ts
  tsconfig.json
  .env.example
  src/
    app/
    shared/
    contexts/
```

Do not introduce `projects`, `issues`, `boards`, or `workflow` contexts before there is an implementation slice that needs them.

Do not create empty architecture folders for future work unless they are part of the active slice.

## Risks

- Developers may put backend business rules into frontend domain models.
- Developers may over-engineer trivial UI with unnecessary layers.
- Contexts may import each other deeply and become coupled.
- Raw fetch calls may still appear in components if not reviewed.
- Auth state may accidentally become persistent.
- Frontend may start before API contracts are stable enough.

## Mitigations

- Keep backend as the source of truth for permissions, workflow, and state changes.
- Review imports between contexts.
- Keep shared code generic and context-free.
- Add ESLint import boundary rules later if drift appears.
- Test auth use cases and HTTP gateway behavior.
- Add review checklist items for raw fetch calls and token storage.
- Keep initial implementation focused on IdentityAccess only.

## Migration / Adoption Plan

1. Keep Symfony backend at repository root.
2. Keep frontend under `frontend/`.
3. Update frontend documentation to reference this ADR.
4. Create minimal Next.js + TypeScript skeleton.
5. Add `src/app`, `src/shared`, and `src/contexts`.
6. Implement `contexts/identity-access` first.
7. Stabilize auth flow.
8. Add tests for login, register, refresh bootstrap, logout, protected routes, and retry-on-401 behavior.
9. Introduce additional contexts only when a real product slice requires them.

## Open Questions

- Which package manager should be used for the frontend?
- Should frontend use plain React state/context, Zustand, TanStack Query, or another state/data layer?
- Should API client be handwritten initially or generated from OpenAPI later?
- Which UI/component system should be adopted?
- Should ESLint import-boundary rules be added immediately or after the first context is implemented?

## References

- Related ADRs:
  - `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
  - `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- Related docs:
  - `frontend/README.md`
  - `docs/design/authentication-api-strategy.md`
  - `docs/architecture/project-architecture-rules.md`
- Related issues: none
- Related PRs: none

## Review Checklist

- [x] Decision is clear and testable.
- [x] Business context is explained.
- [x] Alternatives are documented.
- [x] Trade-offs are explicit.
- [x] Layer impact is described.
- [x] DDD impact is described.
- [x] EDA impact is described if events are involved.
- [x] CQRS impact is described if commands/queries/read models are involved.
- [x] Symfony implementation is treated as implementation detail.
- [x] Risks and mitigations are documented.
