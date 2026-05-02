# ADR-0004: Use API-First Symfony Backend with Next.js Frontend

## Status

Accepted

## Date

2026-05-02

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Backend / Symfony owner
- Frontend owner: Frontend / product UI owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

The product UI is expected to support interactive workflows such as:

- project navigation;
- issue lists;
- issue details;
- boards;
- drag-and-drop status changes;
- filters;
- search;
- inline editing;
- comments;
- notifications;
- activity feeds;
- reporting dashboards.

ADR-0001 established Boardly as a Symfony-based Modular Monolith using Hexagonal Architecture and DDD.

ADR-0002 established context-based source structure under `src/Boardly` and `src/Shared`.

ADR-0003 established transactional outbox for reliable domain event delivery.

We need a clear frontend/backend delivery architecture before implementing product-facing interfaces.

The main decision is whether Boardly should use:

- Symfony + Twig as the primary UI;
- Symfony API + Next.js frontend;
- Symfony with Turbo/Stimulus;
- another frontend delivery model.

Without an explicit decision, the project risks mixing UI strategies and creating unclear boundaries between frontend, controllers, application commands, domain behavior, and API contracts.

## Decision

We will build Boardly as an API-first product.

Symfony will be the backend API and application/domain owner.

Next.js will be the primary frontend application for the product UI.

Twig will not be used for the main product UI.

Twig may be used only for simple technical, internal, or operational pages if there is a concrete reason.

Primary direction:

```text
Next.js frontend -> Symfony HTTP API -> Application layer -> Domain layer
```

Symfony remains responsible for:

- application commands and queries;
- domain behavior;
- permissions and authorization decisions;
- workflow validation;
- persistence;
- transactions;
- domain events;
- outbox storage;
- async side effects orchestration.

Next.js remains responsible for:

- product UI;
- routing and page composition;
- client-side interaction;
- UI state;
- form rendering;
- calling Symfony APIs;
- rendering read models returned by backend APIs.

## Scope

This decision applies to:

- product-facing Boardly UI;
- frontend/backend boundary;
- Symfony HTTP API design;
- API request/response contracts;
- authentication/session strategy planning;
- CORS/cookie planning if frontend and backend are served separately;
- local development structure;
- deployment planning for UI and API;
- future controller and request DTO design.

This decision does not apply to:

- final frontend component library choice;
- final design system;
- final hosting provider;
- final CI/CD structure;
- public integration API versioning;
- mobile app architecture;
- internal one-off operational pages where Twig may be justified.

## Architectural Rules

- Symfony is the backend API and domain/application owner.
- Next.js is the primary product UI.
- Twig must not be used for the main Boardly product UI.
- Frontend must not enforce business invariants as the source of truth.
- Frontend may display allowed actions returned by the backend, but backend must validate every command again.
- Frontend must not decide workflow transitions authoritatively.
- Frontend must not decide permissions authoritatively.
- Symfony controllers must remain thin delivery adapters.
- Symfony controllers translate HTTP input into application commands/queries and return transport responses.
- Symfony controllers must not contain workflow rules, permission rules, or aggregate mutation logic.
- API responses must not expose Doctrine entities directly.
- API DTOs/read models must be explicit.
- Domain objects must not depend on HTTP, JSON, Next.js, React, or frontend concerns.
- API contracts should be use-case oriented where business behavior matters, not blindly CRUD-field oriented.
- Read APIs may use optimized read models or projections when justified.
- Commands and queries remain separated when that improves clarity.

## Alternatives Considered

### Option A: Symfony + Twig as primary UI

Pros:

- Simpler deployment with one backend application.
- Less frontend infrastructure.
- Good fit for classic server-rendered CRUD/admin pages.
- Familiar in Symfony applications.

Cons:

- Less suitable for highly interactive Jira-like UI.
- Boards, drag-and-drop, inline editing, filters, and rich client interactions become harder to maintain.
- Risk of mixing UI logic, controller logic, and application orchestration.
- Harder to create a clear API boundary for future integrations or mobile clients.
- Frontend evolution is more tightly coupled to backend rendering.

### Option B: Symfony API + Next.js frontend

Pros:

- Clear separation between product UI and backend domain/application logic.
- Strong fit for interactive product UI.
- Better frontend developer experience with TypeScript and component-based UI.
- API boundary supports future clients and integrations.
- Easier to keep Symfony controllers as thin delivery adapters.
- Fits CQRS/read-model direction for complex screens.

Cons:

- More infrastructure and tooling.
- Requires API contract discipline.
- Requires authentication/session/CORS decisions.
- Local development has at least two processes.
- Deployment may involve separate frontend and backend artifacts.

### Option C: Symfony + Turbo/Stimulus

Pros:

- Less frontend complexity than full Next.js.
- Keeps server-rendered workflow.
- Can provide moderate interactivity.
- Good for admin panels and simpler products.

Cons:

- May become limiting for complex board interactions and rich product UI.
- Still couples UI rendering strongly to Symfony.
- Can become awkward when product grows toward SPA-like behavior.
- Less clear separation if later a full frontend app is introduced.

### Option D: Full JavaScript backend/frontend stack

Pros:

- One language across frontend and backend.
- Natural integration with Next.js.
- Large ecosystem.

Cons:

- Conflicts with the accepted Symfony backend direction.
- Discards existing Symfony architecture decisions.
- Weakens the current DDD/Hexagonal Symfony learning and implementation focus.
- Requires replacing already accepted backend assumptions without enough benefit.

## Consequences

### Positive

- Product UI can evolve independently from backend domain internals.
- Symfony remains focused on application/domain behavior and infrastructure integration.
- Next.js can handle rich interactions required by a Jira-like product.
- API contracts become explicit.
- Future mobile or external clients become easier to support.
- Backend tests can focus on commands, queries, permissions, workflow, transactions, and API behavior separately.

### Negative

- More moving parts than Twig-only delivery.
- Requires frontend build tooling and package management.
- Requires API contract versioning or compatibility discipline over time.
- Requires decisions about authentication, cookies/tokens, CSRF, and CORS.
- Some simple pages may require more setup than server-rendered Twig.

### Neutral / Operational

- Local development will likely run Symfony and Next.js separately.
- Deployment can be separate or combined depending on hosting strategy.
- Symfony can still serve health checks and technical endpoints.
- Next.js may be deployed as a separate frontend service or static/edge-rendered app depending on future needs.
- API documentation strategy should be decided later.

## Trade-offs

- We accept more frontend infrastructure in exchange for a stronger product UI foundation.
- We accept API contract discipline in exchange for clear frontend/backend boundaries.
- We accept separate development processes in exchange for independent UI evolution.
- We avoid Twig for main product UI in exchange for a frontend stack better suited to boards and rich interactions.
- We keep Twig available for simple technical/internal pages instead of banning it completely.

## Impact on Layers

### Domain

- Domain remains independent from frontend and HTTP concerns.
- Domain must not depend on Next.js, React, JSON serialization, Symfony controllers, or request/response DTOs.
- Business invariants remain in Domain or Application policies/services, not in the frontend.

### Application

- Application commands and queries remain the backend entry point for product behavior.
- Application layer receives already-normalized input from controllers.
- Application layer does not know whether the caller is Next.js, CLI, tests, or another adapter.
- Command handlers should not return complex read models unless the use case explicitly requires it.

### Infrastructure

- Infrastructure continues to implement persistence, messaging, cache, search, and external adapters.
- Infrastructure may include API documentation tooling, serializers, auth adapters, CORS configuration, and security integrations.
- Infrastructure must not become the place where product UI logic is implemented.

### Interfaces / UI

- Symfony HTTP controllers are API delivery adapters.
- Controllers validate transport-level input, create commands/queries, and map results/errors to HTTP responses.
- API request DTOs and response DTOs live in Interfaces/UI or a clearly defined API adapter layer.
- Next.js owns browser UI, components, pages, UI state, and API calls.

## Impact on DDD

This decision keeps Boardly domain modeling on the backend.

Frontend concepts such as pages, components, UI forms, and client state must not define domain ownership.

Example:

```text
A board column may render issue statuses in Next.js,
but Workflow remains responsible for allowed transitions,
and Issues remains responsible for changing issue status through the application use case.
```

Frontend may request available actions from the backend, but backend remains authoritative.

## Impact on EDA

This decision does not introduce new domain events by itself.

Event-driven backend behavior remains governed by ADR-0003.

Frontend may later consume real-time updates through polling, Server-Sent Events, WebSockets, or another mechanism, but that is not decided here.

Important rule:

```text
Frontend real-time updates are projections/notifications of backend facts.
They do not replace backend commands or domain events.
```

## Impact on CQRS

API-first delivery fits CQRS well.

Command endpoints should represent user intent when business behavior matters, for example:

```text
ChangeIssueStatus
AssignIssue
CommentOnIssue
ArchiveProject
```

Query endpoints should return read models optimized for screens, for example:

```text
Issue details
Project board
Issue search results
Activity feed
Reporting dashboard
```

Read models returned to Next.js should be explicit and should not expose Doctrine entities or domain aggregates directly.

## Symfony Implementation Direction

Symfony remains the backend framework and API implementation layer.

Allowed Symfony mechanisms include:

- HTTP controllers for API endpoints;
- Serializer for explicit DTO serialization if controlled carefully;
- Validator for request DTO validation;
- Security component for authentication and authorization integration;
- Voters/Access Decision Manager if they implement backend authorization policies;
- Messenger for command/query dispatch and async processing where appropriate;
- NelmioApiDoc/OpenAPI tooling if later accepted;
- CORS configuration if frontend and backend run on different origins.

Symfony controllers should follow this shape:

```text
HTTP request
-> request DTO / transport validation
-> application command/query
-> handler
-> response DTO or status response
```

Controllers must not:

```text
- mutate Doctrine entities directly
- contain workflow rules
- contain permission rules
- return Doctrine entities directly
- use frontend needs as a reason to bypass application layer
```

## Frontend Implementation Direction

Next.js will be the primary frontend application.

Expected frontend responsibilities:

- page routing;
- layouts;
- components;
- forms;
- client-side state;
- calling Symfony API;
- rendering backend read models;
- handling optimistic UI only when backend conflict handling is clear;
- showing validation and business errors returned by API.

Frontend must treat backend as authoritative for:

- permissions;
- workflow transitions;
- allowed issue operations;
- canonical issue/project/workflow state;
- search truth reconciliation when backend state differs.

The exact frontend stack is not finalized by this ADR.

Open decisions include:

- package manager;
- component library;
- data fetching library;
- form library;
- test tooling;
- generated API client or hand-written API client;
- SSR vs CSR usage per page.

## Repository Structure Direction

Current Symfony backend may remain at repository root for now.

Initial direction:

```text
/
├── src/
├── config/
├── public/
├── docs/
└── frontend/
```

Do not move Symfony into `backend/` immediately unless a separate issue/ADR justifies the repository restructuring.

Reason:

```text
The Symfony skeleton already exists at repository root.
Moving it now would add migration noise without solving a product problem.
```

If repository structure becomes painful later, introduce a separate ADR or design issue for monorepo layout.

## Risks

- Frontend duplicates business rules already enforced by backend.
- API endpoints become CRUD field setters instead of use-case-oriented commands.
- Doctrine entities are serialized directly to JSON.
- Authentication and CSRF/CORS are handled inconsistently.
- Frontend and backend contracts drift without tests or documentation.
- Twig and Next.js are mixed for the same product flows without a clear boundary.
- Optimistic UI hides backend conflicts or failed workflow transitions.
- More infrastructure slows early development.

## Mitigations

- Keep backend authoritative for permissions, workflow, and state changes.
- Use application commands/queries behind API controllers.
- Use explicit request/response DTOs or read models.
- Add API contract tests for important endpoints.
- Add frontend API client layer instead of scattering fetch calls everywhere.
- Define authentication/session strategy before implementing protected product screens.
- Use Twig only for clearly non-product technical/internal pages.
- Treat optimistic UI as a frontend enhancement, not as confirmation of committed backend state.
- Document major API conventions before implementing many endpoints.

## Migration / Adoption Plan

1. Keep existing Symfony backend at repository root.
2. Create `frontend/` only when starting real frontend implementation.
3. Add a minimal Next.js application under `frontend/`.
4. Define local development commands for backend and frontend.
5. Define authentication/session strategy before protected screens.
6. Implement API endpoints through thin Symfony controllers that call application commands/queries.
7. Implement frontend screens against explicit API contracts.
8. Add API contract tests and frontend integration tests as the product UI grows.
9. Revisit deployment topology when the first real frontend screens exist.

## Open Questions

- Should frontend and backend be deployed separately or as one combined artifact?
- Should frontend use cookie-based session auth or token-based auth?
- How should CSRF be handled for browser-based API commands?
- Should CORS be required in production or avoided by same-origin routing/proxying?
- Should API documentation use OpenAPI/NelmioApiDoc?
- Should API client code be generated from OpenAPI or handwritten initially?
- Which Next.js rendering mode should be used for authenticated product pages?
- Which frontend state/data-fetching library should be used?
- Which component/design system should be used?
- Should real-time UI updates use polling, SSE, WebSockets, or Mercure?

## References

- Related ADRs:
  - `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
  - `docs/adr/0002-use-boardly-context-based-source-structure.md`
  - `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`
- Related docs:
  - `docs/architecture/project-architecture-rules.md`
  - `docs/design/change-issue-status.md`
  - `docs/adr/0000-template.md`
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
