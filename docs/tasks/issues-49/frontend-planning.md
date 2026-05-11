# Issue 49 Frontend Re-Planning: Auth Screens and First App Shell

## 1. Inputs and Decision Summary

This plan uses `docs/tasks/issues-49/analysis.md` as the main input. It does not replace the analysis and does not introduce backend work.

Relevant memory found:

- ADR-0005 governs auth: short-lived JWT access tokens, memory-only frontend storage, opaque HttpOnly refresh cookies, refresh/logout with browser cookies, and `X-CSRF-Intent: auth-refresh`.
- ADR-0006 governs frontend structure: `frontend/src/app` for routes/composition, `frontend/src/shared` for generic primitives/infrastructure, and `frontend/src/contexts/identity-access` for auth domain/application/infrastructure/presentation.
- Auth API integration should stay behind application ports and infrastructure gateways. React components must not contain raw `fetch`, token refresh algorithms, or backend error normalization.
- Testing should cover auth use cases, gateway contract mapping, memory-only storage, bootstrap, logout, protected routing, safe errors, and token non-persistence.

Current code facts:

- The frontend lives under `frontend/` and uses Next.js App Router.
- Existing IdentityAccess frontend files already cover login, register, refresh, logout, bootstrap, protected route, memory store, and HTTP gateway.
- Existing routes include `/login`, `/register`, `/dashboard`, and `/`; issue #49 needs `/app/dashboard`, `/pending-approval`, and `/auth/session-loading`.
- `frontend/src/shared/api/http-client.ts` already centralizes fetch and sends `credentials: 'include'`.
- Tailwind, global CSS, and Navy design tokens are not configured.
- No frontend unit/component/e2e test framework is configured; current frontend quality gates are `npm run typecheck`, `npm run lint`, and `npm run build`.

Conflicts between memory/docs and current code:

- ADR/design docs mention `GET /api/auth/me`, but current backend code does not implement it.
- Current frontend calls `/api/auth/me` after login/refresh, which is unsafe for issue #49 unless a backend endpoint is added in a separate backend task.
- `docs/development/frontend.md` describes register returning `id`, `email`, `name`, and `status`; current backend registration returns `accountId` and `status`.
- Current frontend route is `/dashboard`; issue #49 requires `/app/dashboard`.
- Earlier skeleton-oriented memory is stale because auth frontend infrastructure already exists.

Planning decision:

- Issue #49 should finish the existing IdentityAccess frontend slice and first authenticated shell.
- Infrastructure and auth contract fixes must happen before visual work.
- The frontend should adapt safely to current backend contracts for this issue, not require backend changes.
- No Projects, Boards, Tasks, Workflow, Reports, or Admin UI should be implemented.

## 2. Scope

In scope:

- Auth contract alignment in the frontend gateway and use cases.
- Shared auth state shape/provider improvements if needed for consistent guard/form/shell behavior.
- Login, register, pending approval, session loading, and first protected dashboard routes.
- Minimal AppShell/SidebarNav/PageHeader/shared primitives needed by auth screens and `/app/dashboard`.
- Tailwind and Navy token setup.
- Focused verification and test planning.

Out of scope:

- Backend controllers, DTOs, routes, CORS, cookies, or auth behavior changes.
- Admin approval UI.
- Detailed Projects, Boards, Tasks, Workflow, Reports, analytics, automation, or notification UI.
- Storing access tokens in browser persistence.
- Reading, storing, or manually attaching refresh tokens in frontend code.
- Full component-library adoption or shadcn CLI setup.

## 3. Architecture Plan

Use ADR-0006 boundaries:

| Area | Responsibility | Issue #49 direction |
| --- | --- | --- |
| `frontend/src/app` | Next.js routes, layouts, page composition | Add/move routes; compose context/shared UI only |
| `frontend/src/shared/api` | Transport mechanics and normalized HTTP errors | Keep `fetch` centralized and `credentials: 'include'` |
| `frontend/src/shared/ui` | Generic UI primitives | Add only reusable primitives needed by auth/shell |
| `frontend/src/contexts/identity-access/domain` | Frontend auth/session/account types | Keep as frontend models, not backend authority |
| `frontend/src/contexts/identity-access/application` | Login/register/bootstrap/logout flows and ports | Fix flow assumptions around current backend contracts |
| `frontend/src/contexts/identity-access/infrastructure` | Auth HTTP gateway and memory session store | Map backend DTOs safely; hide transport details |
| `frontend/src/contexts/identity-access/presentation` | Forms, auth hooks/provider, guards, logout UI | Render UI state; do not own token refresh algorithms |

Import constraints:

- `app` may import shared UI and context presentation components.
- IdentityAccess application may depend on domain and ports only.
- IdentityAccess infrastructure may depend on application ports/domain and shared API/config.
- Presentation may call hooks/use cases through context APIs, but must not call raw HTTP.
- Shared UI must not import IdentityAccess.

## 4. Auth and API Contract Plan

Frontend must follow current backend code/tests unless a separate backend task changes them.

Required request behavior:

- Login body: `{ email, plainPassword }`.
- Register body: `{ email, plainPassword, name }`.
- Refresh/logout: no body, `credentials: 'include'`, `X-CSRF-Intent: auth-refresh`.
- Protected API requests: `Authorization: Bearer <accessToken>`.
- Never log, display, persist, or expose passwords, access tokens, refresh tokens, or cookie values.

Contract fixes to do before visual work:

- Update `LoginResponse` to include the backend `account` payload if current backend still returns it.
- Build `AuthSession` from login/refresh response data instead of calling missing `/api/auth/me`.
- Remove, isolate, or defer `getMe()` usage unless the backend endpoint exists.
- Update `RegisterResponse` to current backend shape: `accountId` and `status`.
- Ensure registration success does not create a session and routes to `/pending-approval`.
- Keep account lifecycle display driven by backend stable error/status codes, especially `account_not_active`, `invalid_credentials`, and registration conflict/validation errors.

Session rules:

- Access token remains in `AuthMemoryStore` only.
- Refresh token remains browser-managed in an HttpOnly cookie; frontend code must not read `document.cookie` for it.
- Browser reload intentionally loses the access token.
- Bootstrap calls refresh, stores the returned access token in memory on success, and clears memory on invalid/unauthenticated refresh.
- Logout should clear local memory state even if the backend logout call fails, then redirect to `/login`.

## 5. Route and UI Composition Plan

Routes:

| Route | Access | Direction |
| --- | --- | --- |
| `/` | Public | Redirect to `/login` |
| `/login` | Public | Login screen; success routes to `/app/dashboard`; inactive account routes/links to `/pending-approval` |
| `/register` | Public | Register screen; success routes to `/pending-approval`; no session creation |
| `/pending-approval` | Public/auth-adjacent | Simple waiting-for-approval page; no admin UI |
| `/auth/session-loading` | Public/transient | Full-page loading state for bootstrap/navigation if route-based loading is used |
| `/app/dashboard` | Protected | First authenticated placeholder inside app shell |
| `/dashboard` | Compatibility | Prefer redirect to `/app/dashboard` during implementation unless no references remain |

Shared UI primitives needed:

- Existing: `Button`, `Input`.
- Add/update: `PasswordInput`, `FormField`, `Alert`, `Card`, `Badge`, `Skeleton`, `EmptyState`, `PageHeader`, `SidebarNav`, `AppShell`.

Primitive rules:

- Keep shared primitives generic and context-free.
- Use accessible native markup first.
- Add `className` composition support.
- Use restrained SaaS density and cards with radius around 8px.
- Add `forwardRef` where the component wraps native focusable elements.
- Do not add Radix packages unless a component genuinely needs Radix behavior.
- Do not adopt shadcn CLI in this issue.

App shell rules:

- Use a dark Navy sidebar and light content surface.
- Navigation may show only MVP-safe entries, primarily Dashboard.
- Future sections may appear only as non-functional placeholders if needed for layout, not as detailed product UI.
- Account/logout area must not expose token details.
- Layout must work on mobile and desktop without overlapping text.

## 6. Tailwind and Style System Plan

Tailwind is missing today, so style setup must be an early implementation step.

Implementation direction:

- Add the minimal Tailwind/PostCSS setup appropriate for the installed Next.js version at implementation time.
- Add `frontend/src/app/globals.css` and import it from `frontend/src/app/layout.tsx`.
- Centralize Navy tokens as CSS variables.
- Map Tailwind semantic colors to CSS variables if a Tailwind config is introduced.
- Avoid repeated hardcoded hex values in pages/components.
- Use the standalone prototype only as a visual/token reference, not as source code to copy.

Core semantic variables:

```css
:root {
  --background: #F6F8FB;
  --surface: #FFFFFF;
  --foreground: #0B1220;
  --primary: #1D4ED8;
  --primary-foreground: #FFFFFF;
  --accent: #38BDF8;
  --accent-foreground: #0B1220;
  --success: #16A34A;
  --warning: #F59E0B;
  --danger: #EF4444;
  --border: #E2E8F0;
  --muted: #F1F5F9;
  --muted-foreground: #64748B;
  --sidebar: #0B1220;
  --sidebar-foreground: #CBD5E1;
}
```

## 7. Implementation Order

1. Confirm no backend changes are part of the implementation task.
2. Fix frontend auth API contracts and session construction to match current backend behavior.
3. Remove/defer reliance on missing `/api/auth/me`.
4. Normalize register mapping to `accountId`/`status` and route success to `/pending-approval`.
5. Improve auth state sharing if needed so forms, guard, shell, and logout read the same session state.
6. Add route structure: `/app/dashboard`, `/pending-approval`, `/auth/session-loading`, and `/dashboard` redirect.
7. Add minimal Tailwind/global CSS setup and Navy semantic tokens.
8. Upgrade shared primitives needed by auth screens and shell.
9. Rework login/register/pending/session-loading screens.
10. Build protected AppShell and dashboard placeholder.
11. Add safe loading, logout, error, and accessibility states.
12. Run frontend verification and any available focused backend contract checks.

## 8. Verification Plan

Required implementation verification from `frontend/`:

```bash
npm run typecheck
npm run lint
npm run build
```

Manual verification:

- Login sends `plainPassword`.
- Register sends `plainPassword`.
- Refresh/logout include `X-CSRF-Intent: auth-refresh`.
- Requests include `credentials: 'include'`.
- Access token is absent from `localStorage`, `sessionStorage`, IndexedDB, readable cookies, URL params, and env vars.
- Refresh token is never read from frontend JavaScript.
- `/login` success routes to `/app/dashboard`.
- `/register` success routes to `/pending-approval`.
- `/app/dashboard` redirects unauthenticated users to `/login`.
- Reload on `/app/dashboard` attempts refresh bootstrap.
- Logout clears local session and routes to `/login`.
- No detailed Projects/Boards/Tasks/Admin UI appears.

Recommended tests if frontend test tooling is introduced:

- Auth gateway maps login/register/refresh/logout contracts safely.
- `AuthMemoryStore` does not persist tokens.
- Login stores session from backend response without `/api/auth/me`.
- Register does not create a session.
- Bootstrap clears state on invalid refresh.
- Protected route avoids protected-content flash.
- Forms show safe errors and do not leak credentials or token details.

## 9. Risks Carried Into Implementation

- The missing `/api/auth/me` endpoint is the highest contract risk; issue #49 should not depend on it.
- Refresh response may need account data to rebuild a complete session; if backend does not provide it, implementation must choose a safe degraded session shape or split backend support into a separate issue.
- Tailwind setup depends on the exact installed Next/Tailwind versions at implementation time.
- Adding frontend test tooling may expand the package/config footprint; implementation may keep tests as follow-up if the issue scope needs to stay small.
- Shared React auth state can drift if `useAuth()` remains only per-hook local state over a module-level store.
- Cookie/CORS behavior may require browser-level verification with the backend running.
