# Issue 49 Frontend Implementation

## Pass 1: Auth Contracts First

Date: 2026-05-11

## Scope

This pass completed the required pre-implementation confirmations and the Auth Contracts First checklist section only.

Backend code was inspected for current auth contracts, but no backend code was modified.

## Skills and Subagents Used

- Skills: `frontend-auth-session`, `frontend-api-integration`, `frontend-context-architecture`.
- Requested workflow: `frontend-task-implementation`; no matching available skill file was present, so the repository task artifacts and frontend rules were used directly.
- Subagents: `frontend_auth_explorer` and `frontend_auth_code_explorer` were started for frontend auth discovery. They did not return useful code findings before the implementation completed, so the implementation relied on direct repository inspection.

## Relevant Memory Found

- ADR-0005 governs short-lived JWT access tokens, memory-only frontend access-token storage, opaque HttpOnly refresh cookies, and refresh/logout CSRF intent.
- ADR-0006 governs the frontend context-based hexagonal structure: `app/` for route composition, `shared/` for generic infrastructure/UI, and `contexts/identity-access/` for auth domain/application/infrastructure/presentation.
- Auth API calls should stay behind IdentityAccess application ports and infrastructure gateways; React components must not contain raw HTTP calls.
- Memory and docs mention a current-account endpoint, but memory is context only and current code is stronger evidence.

## Current Code Facts

- `LoginController` returns `accessToken`, `tokenType`, `expiresIn`, and embedded `account`.
- `RefreshAuthenticationController` returns only `accessToken`, `tokenType`, and `expiresIn`.
- `RegisterAccountController` returns only `accountId` and `status`.
- `security.yaml` exposes login, refresh, logout, and register as public auth routes; no `/api/auth/me` route is present.
- Frontend `httpRequest` already sends `credentials: 'include'` and attaches `Authorization: Bearer <accessToken>` when an access token is supplied.
- Frontend refresh/logout gateway calls already send `X-CSRF-Intent: auth-refresh`.

## Conflicts Between Memory and Code

- Memory/design artifacts mention `/api/auth/me`, but current backend code does not implement `/api/auth/me`.
- Frontend previously called `/api/auth/me` after login and refresh. This pass removed that dependency.
- Planning expected register data shaped like account fields in some docs, but current backend returns `accountId` and `status`.

## Contract Decisions

- Login session is built from the current backend login response and its embedded `account`.
- Refresh/bootstrap no longer calls `/api/auth/me`. Because the current backend refresh response has no account payload, refresh creates a token-only session with `account: null`.
- `AuthSession.account` is now nullable to represent a refreshed session before a supported current-account endpoint exists.
- `getMe()` was removed from the frontend auth gateway port and HTTP adapter.
- Register mapping now uses `accountId` and `status`.
- Login and register request payloads still use `plainPassword`.
- Refresh and logout still send empty bodies with `X-CSRF-Intent: auth-refresh`.
- Cookie-dependent requests still rely on shared `credentials: 'include'`.
- Protected requests still use shared `Authorization: Bearer <accessToken>` support.

## Files Changed

- `frontend/src/contexts/identity-access/domain/auth-session.ts`
- `frontend/src/contexts/identity-access/application/ports/auth-gateway.ts`
- `frontend/src/contexts/identity-access/application/use-cases/login.ts`
- `frontend/src/contexts/identity-access/application/use-cases/refresh-session.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts`
- `docs/tasks/issues-49/frontend-implementation.md`
- `docs/tasks/issues-49/frontend-verification.md`
- `docs/tasks/issues-49/frontend-checklist.md`

## Remaining Scope

- Session Safety checklist remains mostly unchecked except where directly verified by this contract pass.
- Route work remains for `/app/dashboard`, `/pending-approval`, `/auth/session-loading`, and `/dashboard` redirect.
- UI/Tailwind/app shell work was intentionally not started in this pass.

## Pass 2: Session Safety, Auth Boundaries, and Initial Routes

Date: 2026-05-11

## Scope

This pass completed the Session Safety and Auth State and Boundaries checklist sections, then started the Routes section because the session/auth-state changes passed verification.

No backend code, Tailwind/design-token setup, shared visual primitive work, or detailed Projects/Boards/Tasks/Admin UI was changed.

## Skills and Subagents Used

- Skills: `frontend-auth-session`, `frontend-api-integration`, `frontend-context-architecture`, `frontend-review-checklist`.
- Requested workflow: `frontend-task-implementation`; no matching available skill file was present, so this pass followed the existing issue artifacts and one-checkbox-at-a-time implementation discipline.
- Subagents: `identity_access_session_review`, `frontend_boundary_review`, `auth_session_explorer`, and `route_explorer` were started for subagent-driven review. The role-specific review sessions returned process acknowledgements rather than code findings; explorer sessions did not return actionable findings before verification completed, so final decisions are backed by direct source inspection and command evidence.

## Relevant Memory Found

- Access tokens must remain memory-only and must not be persisted to `localStorage`, `sessionStorage`, IndexedDB, readable cookies, URLs, persisted React state, or `NEXT_PUBLIC_*` env vars.
- Refresh tokens are browser-managed HttpOnly cookies. Frontend code must not read or store refresh-token values.
- Invalid refresh must clear frontend memory session state.
- Logout must clear local auth state as part of ending the frontend session.
- ADR-0006 keeps raw HTTP calls in shared/infrastructure code, backend DTO mapping in IdentityAccess infrastructure, and application use cases behind ports.

## Current Code Facts

- `AuthMemoryStore` remains module-memory only.
- Shared `httpRequest` is still the only raw `fetch` location in `frontend/src`.
- `AuthHttpGateway` remains the IdentityAccess infrastructure adapter for backend auth contract mapping.
- `AuthProvider` now wraps the app from `frontend/src/app/providers.tsx`, so guard, forms, dashboard, and logout share one React auth state surface.
- `ProtectedRoute` now tracks bootstrap completion before redirecting unauthenticated users.
- `/dashboard` now redirects to `/app/dashboard`.
- `/app/dashboard`, `/pending-approval`, and `/auth/session-loading` now exist.

## Conflicts Between Memory and Code

- No new conflicts were found in this pass.
- The existing refresh limitation remains intentional: refresh restores a token-only session with `account: null` because the current backend refresh response does not return account details.

## Session-Safety Decisions

- Access tokens continue to live only inside `AuthMemoryStore` and the in-memory `AuthSession`.
- `AuthHttpGateway.refreshSession()` maps unauthenticated refresh responses to `AuthError('invalid_refresh_token')`, allowing `bootstrapSessionUseCase()` to clear memory.
- `logoutUseCase()` now clears `AuthMemoryStore` in a `finally` block, so local session state is cleared even when the backend logout request fails.
- Frontend code still does not read `document.cookie`, `localStorage`, `sessionStorage`, or IndexedDB for token values.
- No refresh-token variable, readable cookie handling, token URL parameter, persisted state library, or token env-var usage was added.

## Auth-State and Boundary Decisions

- `useAuth` was converted into an `AuthProvider`/`useAuth` context pair while preserving the same application use cases and memory store.
- Presentation still triggers `bootstrap()` and `logout()` through the auth hook; the refresh/logout algorithms remain in application use cases and infrastructure adapters.
- Backend contract mapping remains in `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts`.
- App routes only compose presentation components or redirect; they do not build API requests.

## Route Decisions

- `/` remains a redirect to `/login`.
- `/login` and `/register` remain public routes.
- Login success now routes to `/app/dashboard`.
- Register success now routes to `/pending-approval` and still does not create an authenticated session.
- `/pending-approval` is a simple public/auth-adjacent page with a return-to-login link and no admin controls.
- `/auth/session-loading` is a simple loading route.
- `/app/dashboard` is protected and remains placeholder-only.
- `/dashboard` redirects to `/app/dashboard` for compatibility.
- `ProtectedRoute` renders a loading state while bootstrap is pending, preventing protected content from rendering for an unauthenticated reload before bootstrap completes.

## Files Changed

- `frontend/src/app/app/dashboard/page.tsx`
- `frontend/src/app/auth/session-loading/page.tsx`
- `frontend/src/app/dashboard/page.tsx`
- `frontend/src/app/pending-approval/page.tsx`
- `frontend/src/app/providers.tsx`
- `frontend/src/contexts/identity-access/application/use-cases/logout.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts`
- `frontend/src/contexts/identity-access/presentation/guards/ProtectedRoute.tsx`
- `frontend/src/contexts/identity-access/presentation/hooks/useAuth.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/RegisterForm.tsx`
- `docs/tasks/issues-49/frontend-implementation.md`
- `docs/tasks/issues-49/frontend-verification.md`
- `docs/tasks/issues-49/frontend-checklist.md`

## Remaining Scope

- Tailwind/Navy tokens and shared UI primitive work remain unchecked.
- Screen visual/accessibility upgrades remain unchecked except for the route existence and redirect behavior handled here.
- App shell work remains unchecked; `/app/dashboard` is intentionally still a minimal protected placeholder.
- Browser/API runtime verification with a live backend remains deferred.
