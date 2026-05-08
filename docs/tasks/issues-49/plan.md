# Issue 49 Implementation Plan: Auth Screens and First App Shell

## 1. Proposed file tree

Planned source changes for the later implementation, not part of this planning task:

```text
frontend/src/
  app/
    globals.css
    layout.tsx
    page.tsx
    login/
      page.tsx
    register/
      page.tsx
    pending-approval/
      page.tsx
    auth/
      session-loading/
        page.tsx
    app/
      dashboard/
        page.tsx

  shared/
    ui/
      Alert.tsx
      AppShell.tsx
      Badge.tsx
      Button.tsx
      Card.tsx
      EmptyState.tsx
      FormField.tsx
      Input.tsx
      PageHeader.tsx
      PasswordInput.tsx
      SidebarNav.tsx
      Skeleton.tsx

  contexts/
    identity-access/
      application/
        ports/
          auth-gateway.ts
          auth-session-store.ts
        use-cases/
          bootstrap-session.ts
          login.ts
          logout.ts
          refresh-session.ts
          register.ts
      domain/
        account.ts
        account-status.ts
        auth-error.ts
        auth-session.ts
      infrastructure/
        http/
          auth-api-contracts.ts
          auth-http-gateway.ts
        state/
          auth-memory-store.ts
      presentation/
        guards/
          ProtectedRoute.tsx
        hooks/
          useAuth.ts
        ui/
          LoginForm.tsx
          LogoutButton.tsx
          RegisterForm.tsx
```

Do not create Projects, Issues, Boards, Reports, Admin, analytics, or automation contexts for this milestone.

## 2. Step-by-step implementation plan

1. Add Navy global tokens in `frontend/src/app/globals.css` and import them from `frontend/src/app/layout.tsx`.
2. Upgrade shared primitives incrementally, keeping them generic and context-free.
3. Fix current auth contract mappings to match backend code/tests:
   - login response includes `account`;
   - register response currently includes `accountId` and `status`;
   - do not rely on `/api/auth/me` unless backend adds it in an allowed task.
4. Refine auth use cases and session state so login/refresh create an `AuthSession` without storing tokens outside memory.
5. Improve `useAuth`/provider shape if needed so session state is shared consistently across guards, forms, shell, and logout.
6. Update routes:
   - keep `/login`;
   - keep `/register`;
   - add `/pending-approval`;
   - add `/auth/session-loading`;
   - add `/app/dashboard`;
   - redirect old `/dashboard` if retained, or remove only if no references remain.
7. Build auth screens using reusable primitives and IdentityAccess presentation components.
8. Build `AppShell` and `SidebarNav` for authenticated pages only.
9. Implement dashboard as a restrained authenticated placeholder, not detailed product UI.
10. Implement logout as a best-effort backend call plus local memory clear and redirect to `/login`.
11. Run focused verification commands later.

## 3. Design token plan

Create `frontend/src/app/globals.css` with:

- CSS reset/base box sizing.
- `body` background `#F6F8FB`, text `#0B1220`, 14px base font, system/Inter-style font stack.
- CSS variables for primary, accent, background, surface, text, success, warning, danger, border, muted text, focus ring, spacing, radius, and shadow.
- Dark Navy sidebar variables:
  - sidebar background `#0B1220`;
  - sidebar foreground `#CBD5E1`;
  - active background `#1D4ED8`;
  - hover background `#1E293B`.
- Accessible `:focus-visible` styles.
- Utility-free component class styles owned by each component or global semantic classes, depending on existing style direction chosen during implementation.

Do not copy the standalone prototype HTML or generated script output.

## 4. API/auth client plan

Keep raw `fetch` centralized in `frontend/src/shared/api/http-client.ts`.

Required API behavior:

- Always use `credentials: 'include'`.
- Send JSON `Content-Type` only when a body exists.
- Send `Authorization: Bearer <accessToken>` for protected requests.
- Normalize backend error envelopes into `ApiError`.
- Keep one 401 retry support, but avoid infinite loops.
- Keep refresh/logout `X-CSRF-Intent: auth-refresh`.

Update `AuthHttpGateway` contracts:

- `LoginResponse` should include the backend `account` object if current backend remains unchanged.
- `RegisterResponse` should match current backend `accountId` and `status`, or implementation must first confirm backend has changed.
- Remove or defer `/api/auth/me` usage unless a backend route exists.

## 5. Session/auth state plan

Keep `AuthMemoryStore` as the only access-token storage.

Session shape:

- `accessToken`
- `expiresAt`
- `account`

Provider/hook direction:

- Prefer a single client-side `AuthProvider` in `frontend/src/app/providers.tsx` or IdentityAccess presentation if shared React state is needed.
- Keep gateway/store construction outside render or memoized.
- Expose `session`, `isLoading`, `login`, `register`, `bootstrap`, and `logout`.
- Do not persist auth state to browser storage.

Bootstrap:

- On protected routes, enter loading state.
- Call refresh with cookie credentials and CSRF intent header.
- If refresh succeeds, store new access token in memory only.
- If refresh fails with invalid refresh token/401, clear memory and redirect to `/login`.
- Use `/auth/session-loading` for a full-page loading state where navigation needs it.

## 6. Routing/protection plan

Routes:

- `/` redirects to `/login` unless future product requirements change this.
- `/login` is public.
- `/register` is public.
- `/pending-approval` is public/auth-adjacent.
- `/auth/session-loading` is a transient loading page.
- `/app/dashboard` is protected.

Protection:

- `ProtectedRoute` remains in IdentityAccess presentation.
- It should render a polished loading state or route to `/auth/session-loading` during bootstrap.
- It should redirect unauthenticated users to `/login`.
- It should avoid protected content flash before bootstrap completes.

## 7. Component plan

Button:

- Variants: primary, secondary, ghost, danger.
- Sizes suitable for 14px SaaS density.
- Disabled/loading state.
- Native button semantics preserved.

Input:

- Generic text input with forwarded standard input props.
- Visible focus state, disabled state, invalid state.
- Does not own labels by itself.

PasswordInput:

- Wraps `Input` with show/hide control.
- Button must have accessible label.
- Does not store password beyond React form state.

FormField:

- Owns label, description/help, error message, and `aria-describedby` wiring.
- Works with `Input` and `PasswordInput`.

Alert:

- Variants: info, success, warning, danger.
- Use `role="alert"` or `aria-live` for form errors and status updates where appropriate.

Card:

- Generic contained surface for auth forms and repeated dashboard placeholders.
- Radius should stay restrained, around 8px.

Badge:

- Status labels such as `Pending approval`.
- Variants for neutral/success/warning/danger.

Skeleton:

- Loading placeholders for dashboard/session shell.
- Accessible non-annoying loading text where needed.

EmptyState:

- For dashboard placeholder content only.
- Avoid marketing-style hero copy.

PageHeader:

- Page title, short description, optional actions.
- Used inside app shell content.

SidebarNav:

- Dark Navy vertical navigation.
- Include only safe MVP entries, such as Dashboard plus disabled/placeholder sections if necessary.
- No detailed Projects/Boards/Tasks/Admin implementation.

AppShell:

- Dark sidebar, main content area, header/account area, logout placement.
- Responsive layout with mobile-safe navigation.
- Used by `/app/dashboard`.

## 8. Page plan

`/login`:

- Public auth layout.
- Boardly mark/name, email field, password field, submit button, link to register.
- On success, route to `/app/dashboard`.
- On `account_not_active`, route or link to `/pending-approval`.
- On invalid credentials, show generic safe error.

`/register`:

- Public auth layout.
- Name, email, password fields.
- Use `plainPassword` internally for API payload.
- On success, route to `/pending-approval`.
- On duplicate email, show safe conflict message.
- Do not create a session after registration.

`/pending-approval`:

- Public auth-adjacent page.
- Explain that admin approval is required before login.
- Include link/button back to `/login`.
- No admin controls.

`/auth/session-loading`:

- Minimal full-page loading state.
- Used during bootstrap/navigation if route-based loading is chosen.
- No token details or backend internals displayed.

`/app/dashboard`:

- Protected route.
- Uses `AppShell`.
- Shows simple dashboard overview/empty state only.
- Avoid Projects, Boards, Tasks, Reports, Admin approval, analytics, automation, or future issue-management UI.

## 9. Logout behavior plan

Logout flow:

1. Disable logout control and show pending state.
2. Call `POST /api/auth/logout` with `credentials: 'include'` and `X-CSRF-Intent: auth-refresh`.
3. Clear `AuthMemoryStore`.
4. Clear React auth state.
5. Redirect to `/login`.

Failure policy:

- Prefer clearing local memory state even if logout returns a network/server error, because the user explicitly requested local sign-out.
- Do not reveal whether a refresh token was present, valid, expired, revoked, or unknown.
- Optionally show a generic message only if redirect is blocked.

## 10. Accessibility checklist

- Every input has a visible `<label>`.
- Form errors are associated with fields through `aria-describedby`.
- Form-level errors use `role="alert"` or polite `aria-live`.
- Buttons expose loading/disabled state without changing layout unexpectedly.
- Password visibility toggle has an accessible name.
- Focus states are visible against Navy and light surfaces.
- Color contrast meets WCAG AA for text and controls.
- Sidebar navigation uses semantic `<nav>` with an accessible label.
- Current nav item uses `aria-current="page"`.
- Protected loading states do not trap keyboard focus.
- Layout works at mobile and desktop widths without overlapping text.
- Pages use semantic landmarks: `main`, `nav`, `header` where appropriate.

## 11. Testing plan

Current repo support:

- No frontend unit/component/e2e framework is configured.
- Existing frontend verification commands are `npm run typecheck`, `npm run lint`, and `npm run build`.

Unit/component tests if frontend test tooling is added later:

- `AuthMemoryStore` never persists access token to browser storage.
- login use case stores access token in memory and maps account data.
- register use case sends `plainPassword` and does not create a session.
- bootstrap calls refresh and clears state on invalid refresh.
- logout clears state after backend call or best-effort failure policy.
- `LoginForm` handles invalid credentials and account-not-active safely.
- `RegisterForm` handles validation/conflict safely.
- `ProtectedRoute` avoids protected-content flash and redirects unauthenticated users.

Route/auth behavior tests if e2e tooling is added later:

- `/login` success routes to `/app/dashboard`.
- `/register` success routes to `/pending-approval`.
- `/app/dashboard` redirects unauthenticated users to `/login`.
- reload on `/app/dashboard` refreshes session through cookie flow.
- logout clears local state and routes to `/login`.
- no access token appears in `localStorage` or `sessionStorage`.

Manual verification checklist:

- Login payload uses `plainPassword`.
- Register payload uses `plainPassword`.
- Refresh/logout include `X-CSRF-Intent: auth-refresh`.
- Requests include credentials.
- Access token is not persisted in storage.
- Refresh token is never read from JavaScript.
- Auth pages match Navy visual direction.
- Dashboard route is `/app/dashboard`.
- No detailed Projects/Boards/Tasks/Admin UI appears.
- Keyboard navigation and focus rings work.

## 12. Focused verification commands for later

Run from `frontend/` after implementation:

```bash
npm run typecheck
npm run lint
npm run build
```

Optional backend contract checks if frontend behavior depends on backend responses:

```bash
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth
```

No full verification is required during this planning-only task.

## 13. Open questions/blockers

- Should issue #49 adapt frontend to current backend login response with embedded `account`, or should backend add `/api/auth/me` first in a separate issue?
- Should the current `/dashboard` route redirect to `/app/dashboard` for compatibility, or be removed during implementation?
- Should frontend test tooling be introduced in issue #49, or deferred to keep package/config changes smaller?
- Should Navy tokens live directly on `:root`, or under a `.boardly` root class for future embedding/isolation?
- Should logout clear local memory state even if the backend logout request fails? Recommended: yes, with backend logout treated as best effort.
