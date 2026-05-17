# Frontend Analysis: Projects slice for issue #60

## 1. Skill used

- `frontend-task-analysis`

## 2. Relevant files discovered

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/development/backend/boardly-symfony-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `frontend/package.json`
- `frontend/src/app/layout.tsx`
- `frontend/src/app/page.tsx`
- `frontend/src/app/dashboard/page.tsx`
- `frontend/src/app/app/dashboard/page.tsx`
- `frontend/src/app/login/page.tsx`
- `frontend/src/app/register/page.tsx`
- `frontend/src/app/pending-approval/page.tsx`
- `frontend/src/app/auth/session-loading/page.tsx`
- `frontend/src/app/providers.tsx`
- `frontend/src/shared/api/http-client.ts`
- `frontend/src/shared/api/api-error.ts`
- `frontend/src/shared/config/env.ts`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/Input.tsx`
- `frontend/src/shared/ui/FormField.tsx`
- `frontend/src/shared/ui/PasswordInput.tsx`
- `frontend/src/shared/ui/Alert.tsx`
- `frontend/src/contexts/identity-access/**`
- `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php`
- `src/Boardly/Projects/Interfaces/Http/Response/ListProjectsResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Response/ProjectListItemResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Response/GetProjectResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Response/CreateProjectResponseDto.php`
- `src/Boardly/Projects/Interfaces/Http/Request/CreateProjectRequestDto.php`
- `src/Boardly/Projects/Interfaces/Http/OpenApi/Schema/*.php`
- `tests/Boardly/Projects/Interfaces/Http/Controller/ProjectControllerTest.php`

## 3. Current frontend architecture facts

- The frontend currently has one product context only: `identity-access`.
- There is no `frontend/src/contexts/projects/` yet.
- `frontend/src/app` currently contains the routing layer only:
  - `/` redirects to `/login`
  - `/login` and `/register` are public auth screens
  - `/pending-approval` is a public status page
  - `/auth/session-loading` renders the session restore state
  - `/dashboard` redirects to `/app/dashboard`
  - `/app/dashboard` is the current protected shell page
- `frontend/src/app/providers.tsx` installs the global `AuthProvider`.
- The protected shell is already built around `ProtectedRoute`, `AppShell`, `SidebarNav`, `SidebarAccountCard`, and `LogoutButton`.
- The current dashboard page is still static/prototype data, not Projects-backed state.
- The app shell and shared UI primitives already establish a reusable visual pattern for future protected pages.

## 4. Current Projects API contract facts

- The backend Projects controller is thin and uses `CommandBusInterface` and `QueryBusInterface`.
- All Projects endpoints require bearer authentication and derive the current account from the authenticated token.
- Endpoints currently available:
  - `POST /api/projects`
  - `GET /api/projects`
  - `GET /api/projects/{projectId}`
  - `POST /api/projects/{projectId}/archive`
  - `DELETE /api/projects/{projectId}`
- `POST /api/projects` accepts:
  - `name` required
  - `iconKey` optional
  - `iconKey` defaults to `folder` when omitted, per controller docs
- Create response shape:
  - `id`
  - `name`
  - `iconKey`
  - `status`
  - `createdAt`
  - `updatedAt`
  - `archivedAt`
- List response shape:
  - `{ projects: [...] }`
  - each item contains `id`, `name`, `iconKey`, `status`, `createdAt`
- Get response shape:
  - `id`, `name`, `iconKey`, `status`, `createdAt`, `updatedAt`, `archivedAt`
- Archive and delete return `204 No Content`.
- Validation failures on create return `422`.
- Unauthorized or missing bearer token returns `401`.
- Missing or inaccessible projects return `404` with `project_not_found`.
- The tests show archived projects are omitted from the list result.
- The backend remains authoritative for ownership, visibility, and all project lifecycle decisions.

## 5. Auth/API integration implications

- The current frontend auth model is already aligned with ADR-0005:
  - access token is memory-only
  - refresh token remains in an HttpOnly cookie
  - `credentials: 'include'` is used by the shared HTTP client
- `frontend/src/shared/api/http-client.ts` already accepts an optional `accessToken` and can retry once after `401` via `onUnauthorized`.
- `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts` shows the intended pattern:
  - login/refresh/me/logout are done through the shared HTTP client
  - refresh/logout include the refresh-intent header
  - auth errors are normalized at the boundary
- A Projects slice should follow the same pattern:
  - use a context-specific gateway in `projects/infrastructure/http`
  - pass the bearer token from the in-memory auth session
  - never read tokens from browser storage or cookies
  - let the backend validate access and project ownership
- Protected Projects pages should bootstrap through the existing auth session flow rather than re-implement auth logic.
- If Projects requests need retry-on-expiry behavior, they should reuse the shared `httpRequest(..., { onUnauthorized })` path instead of inventing a second refresh strategy.

## 6. UI/state/tooling facts

- Shared UI primitives already exist for the first protected product pages:
  - `AppShell`
  - `PageHeader`
  - `SidebarNav`
  - `Card`
  - `Badge`
  - `Button`
  - `EmptyState`
  - `Skeleton`
  - `Alert`
  - `FormField`
  - `Input`
  - `PasswordInput`
- The current visual system uses CSS variables and project-owned primitives, but `frontend/package.json` does not yet include Tailwind, Radix, or class-variance-authority dependencies.
- That means the codebase currently satisfies the "shared primitives + CSS variables" part of ADR-0007, but not the full Tailwind/shadcn implementation stack.
- `frontend/package.json` scripts are:
  - `dev`
  - `build`
  - `start`
  - `lint`
  - `typecheck`
- There is no frontend test runner configuration in the repo-owned `frontend/` tree.
- There are no repo-owned frontend test files under `frontend/` today.

## 7. Risks/blockers

- No `projects` frontend context exists yet, so the first slice needs new context boundaries, not just pages.
- The current protected app shell is still prototype/static, so it does not yet consume Projects data.
- The route tree has a redirect-only `/dashboard` page plus the actual protected `/app/dashboard` page. That should be kept consistent before adding more protected routes.
- The Projects API contract is explicit but small; the frontend must map the transport shapes rather than assume a generic CRUD model.
- There is no frontend test infrastructure yet, which is a blocker for meaningful slice-level regression coverage unless the task also introduces it.
- Because the issue body was not directly retrievable from the local repo, the implementation plan should be validated against the branch brief before coding begins.

## 8. Recommended next step: planning

- Define the first Projects slice scope explicitly:
  - list-only landing page, or
  - list plus create, or
  - list plus detail plus actions
- Create the `projects` frontend context boundaries before wiring pages.
- Reuse the existing auth session/access-token flow and shared HTTP client conventions.
- Decide the protected route and shell composition for Projects screens.
- Add the smallest viable frontend test strategy for the slice if the implementation will introduce stateful UI and API mapping.
