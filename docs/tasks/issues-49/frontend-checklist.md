# Issue 49 Frontend Implementation Checklist

Do not mark implementation items complete during planning. Check items only when the implementation actually lands.

## 1. Pre-Implementation

- [x] Read `docs/tasks/issues-49/analysis.md`.
- [x] Read `docs/tasks/issues-49/frontend-planning.md`.
- [x] Confirm issue #49 implementation is frontend-only.
- [x] Confirm no secrets, `.env*`, dumps, local DB files, private keys, logs, `vendor/`, `node_modules/`, `.next/`, or build artifacts are inspected.
- [x] Confirm current backend auth response shapes before editing frontend contracts.
- [x] Confirm whether `/dashboard` should redirect to `/app/dashboard` or be removed after reference cleanup.

## 2. Auth Contracts First

- [x] Update frontend login response contract to match current backend.
- [x] Build login session without relying on missing `/api/auth/me`.
- [x] Update refresh/session bootstrap so it does not depend on missing `/api/auth/me`.
- [x] Remove, isolate, or defer `getMe()` usage unless backend support exists.
- [x] Update register response contract to current backend `accountId` and `status`.
- [x] Keep login request body as `{ email, plainPassword }`.
- [x] Keep register request body as `{ email, plainPassword, name }`.
- [x] Keep refresh request body empty.
- [x] Keep logout request body empty.
- [x] Keep refresh/logout header `X-CSRF-Intent: auth-refresh`.
- [x] Preserve `credentials: 'include'` on cookie-dependent requests.
- [x] Preserve `Authorization: Bearer <accessToken>` for protected requests.

## 3. Session Safety

- [x] Keep access token in memory only.
- [x] Do not store access token in `localStorage`.
- [x] Do not store access token in `sessionStorage`.
- [x] Do not store access token in IndexedDB.
- [x] Do not store access token in readable cookies.
- [x] Do not put access token in URL params.
- [x] Do not put access token in persisted React state libraries.
- [x] Do not put access token in `NEXT_PUBLIC_*` env vars.
- [x] Never read or store the refresh token in frontend code.
- [x] Do not inspect `document.cookie` for auth token values.
- [x] Clear memory session on invalid refresh.
- [x] Clear local memory session on logout even if backend logout fails.
- [x] Do not log passwords, access tokens, refresh tokens, or cookie values.

## 4. Auth State and Boundaries

- [x] Keep raw HTTP calls out of pages and React components.
- [x] Keep backend contract mapping in IdentityAccess infrastructure.
- [x] Keep token refresh/bootstrap algorithms out of presentation components.
- [x] Ensure application use cases depend on ports, not concrete HTTP clients.
- [x] Ensure shared UI does not import IdentityAccess code.
- [x] Ensure `app/` routes compose components rather than owning API logic.
- [x] Improve shared auth React state if needed so guard, forms, shell, and logout stay consistent.

## 5. Routes

- [x] Keep `/` redirecting to `/login`.
- [x] Keep `/login` public.
- [x] Keep `/register` public.
- [x] Add `/pending-approval`.
- [x] Add `/auth/session-loading`.
- [x] Add protected `/app/dashboard`.
- [x] Redirect old `/dashboard` to `/app/dashboard` unless safely removed.
- [x] Update login success destination from `/dashboard` to `/app/dashboard`.
- [x] Update register success destination to `/pending-approval`.
- [x] Ensure unauthenticated `/app/dashboard` access redirects to `/login`.
- [x] Ensure protected content does not flash before bootstrap finishes.

## 6. Tailwind and Tokens

- [x] Add minimal Tailwind/PostCSS setup if still absent.
- [x] Add `frontend/src/app/globals.css`.
- [x] Import global CSS from `frontend/src/app/layout.tsx`.
- [x] Define Navy semantic CSS variables in one place.
- [x] Map Tailwind theme colors to CSS variables if a config is introduced.
- [x] Use semantic classes/tokens in components.
- [x] Avoid repeated hardcoded Navy hex values in pages/components.
- [x] Do not copy standalone prototype HTML, bundled scripts, or generated CSS.
- [x] Add visible `:focus-visible` styling.
- [x] Keep body typography around the intended SaaS density.

## 7. Shared UI Primitives

- [x] Update `Button` with variants, sizes, disabled/loading state, `className`, and native semantics.
- [x] Update `Input` with invalid/disabled/focus states, `className`, and native semantics.
- [ ] Add `PasswordInput` with accessible show/hide control.
- [ ] Add `FormField` with label, description, error, and `aria-describedby` wiring.
- [x] Add `Alert` with safe status/error variants.
- [x] Add `Card` with restrained radius and semantic surface styling.
- [x] Add `Badge` for simple statuses.
- [x] Add `Skeleton` for loading states.
- [ ] Add `EmptyState` for dashboard placeholder content.
- [ ] Add `PageHeader` for app content pages.
- [ ] Add `SidebarNav` for the app shell.
- [ ] Add `AppShell` for authenticated pages.
- [x] Do not add Radix packages unless a component genuinely needs them.
- [x] Do not introduce shadcn CLI setup in this issue.

## 8. Screens

- [ ] Rework `/login` with labeled email/password fields.
- [ ] Show generic invalid credentials errors.
- [ ] Handle `account_not_active` by routing or linking to `/pending-approval`.
- [ ] Rework `/register` with name/email/password fields.
- [ ] Show safe validation/conflict errors.
- [ ] Ensure registration does not create an authenticated session.
- [ ] Implement `/pending-approval` with login return path.
- [ ] Ensure `/pending-approval` has no admin controls.
- [ ] Implement `/auth/session-loading` with safe loading copy/skeleton.
- [ ] Implement `/app/dashboard` as a protected placeholder only.
- [ ] Ensure `/app/dashboard` uses `AppShell`.
- [ ] Ensure logout has loading/disabled behavior.
- [ ] Ensure logout does not reveal refresh token validity.

## 9. App Shell

- [ ] Add dark Navy sidebar.
- [ ] Add accessible navigation landmark.
- [ ] Mark current nav item with `aria-current="page"`.
- [ ] Include only MVP-safe navigation entries.
- [ ] Do not implement detailed Projects UI.
- [ ] Do not implement detailed Boards UI.
- [ ] Do not implement detailed Tasks UI.
- [ ] Do not implement Admin approval UI.
- [ ] Add account/logout area without token details.
- [ ] Verify layout at mobile and desktop widths.

## 10. Accessibility

- [ ] Every input has a visible label.
- [ ] Field errors are associated with fields.
- [ ] Form-level errors use `role="alert"` or `aria-live`.
- [ ] Password visibility toggle has an accessible name.
- [ ] Focus states are visible on light and Navy surfaces.
- [ ] Color contrast is suitable for text and controls.
- [ ] Loading states do not trap focus.
- [ ] Buttons do not resize unexpectedly when loading.
- [ ] Text does not overlap or overflow on mobile.
- [ ] Pages use semantic `main`, `nav`, and `header` where appropriate.

## 11. Verification

- [x] Run `npm run typecheck` from `frontend/`.
- [x] Run `npm run lint` from `frontend/`.
- [x] Run `npm run build` from `frontend/`.
- [x] Manually verify login payload uses `plainPassword`.
- [x] Manually verify register payload uses `plainPassword`.
- [x] Manually verify refresh/logout include `X-CSRF-Intent: auth-refresh`.
- [x] Manually verify requests include `credentials: 'include'`.
- [ ] Manually verify access token is not in browser storage.
- [x] Manually verify refresh token is never read by frontend JavaScript.
- [x] Manually verify `/login` success routes to `/app/dashboard`.
- [x] Manually verify `/register` success routes to `/pending-approval`.
- [x] Manually verify unauthenticated `/app/dashboard` routes to `/login`.
- [ ] Manually verify logout clears local state and routes to `/login`.
- [ ] Manually verify no detailed Projects/Boards/Tasks/Admin UI appears.

## 12. Optional Tests If Tooling Is Added

- [ ] Test auth gateway login mapping.
- [ ] Test auth gateway register mapping.
- [ ] Test refresh/logout headers.
- [ ] Test memory store does not persist tokens.
- [ ] Test login stores session without `/api/auth/me`.
- [ ] Test register does not create session.
- [ ] Test bootstrap clears state on invalid refresh.
- [ ] Test logout clears state on backend failure policy.
- [ ] Test protected route avoids content flash.
- [ ] Test auth forms show safe errors.
