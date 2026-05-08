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

Tailwind setup files may also be needed during later implementation because the repo currently has no Tailwind config or PostCSS config:

```text
frontend/
  postcss.config.*
  tailwind.config.*
```

Use the current Tailwind setup recommended for the installed Next.js version at implementation time. Do not modify package or config files during this planning-only task.

## 2. Step-by-step implementation plan

1. Add the minimal Tailwind CSS setup if it is still absent:
   - add only the required Tailwind/PostCSS packages;
   - add the smallest required config files;
   - wire the global CSS entrypoint for the current Next.js App Router setup.
2. Add Navy global tokens in `frontend/src/app/globals.css` and import them from `frontend/src/app/layout.tsx`.
3. Map Tailwind theme colors to CSS variables if a Tailwind config exists or is introduced.
4. Use Tailwind utility classes in shared primitives and page composition; avoid repeated hardcoded hex colors in components/pages.
5. Upgrade shared primitives incrementally, keeping them generic and context-free.
6. Implement shadcn/Radix-style local primitives:
   - composable APIs;
   - accessible markup;
   - variant-based styling;
   - `className` override support;
   - `forwardRef` where wrapping native elements makes it useful.
7. Do not install or require the full shadcn CLI unless the repo explicitly adopts it later.
8. Do not add Radix packages unless a component genuinely needs Radix behavior.
9. Prefer local Tailwind primitives for `Button`, `Input`, `Card`, `Badge`, and `Skeleton`.
10. Fix current auth contract mappings to match backend code/tests:
   - login response includes `account`;
   - register response currently includes `accountId` and `status`;
   - do not rely on `/api/auth/me` unless backend adds it in an allowed task.
11. Refine auth use cases and session state so login/refresh create an `AuthSession` without storing tokens outside memory.
12. Improve `useAuth`/provider shape if needed so session state is shared consistently across guards, forms, shell, and logout.
13. Update routes:
   - keep `/login`;
   - keep `/register`;
   - add `/pending-approval`;
   - add `/auth/session-loading`;
   - add `/app/dashboard`;
   - redirect old `/dashboard` if retained, or remove only if no references remain.
14. Build auth screens using reusable primitives and IdentityAccess presentation components.
15. Build `AppShell` and `SidebarNav` for authenticated pages only.
16. Implement dashboard as a restrained authenticated placeholder, not detailed product UI.
17. Implement logout as a best-effort backend call plus local memory clear and redirect to `/login`.
18. Run focused verification commands later.

## 3. Tailwind CSS plan

Current state:

- Tailwind CSS is not installed in `frontend/package.json`.
- No `tailwind.config.*` exists.
- No `postcss.config.*` exists.
- No global CSS entrypoint exists.
- No Vite config exists because the frontend uses Next.js App Router.

Implementation direction:

- Use Tailwind CSS as the utility layer for shared primitives, auth pages, and the app shell.
- Add only the minimal Tailwind/PostCSS setup required by the current Next.js version.
- If a Tailwind config exists or is introduced, map semantic theme colors to CSS variables:
  - `background: var(--background)`
  - `surface: var(--surface)`
  - `foreground: var(--foreground)`
  - `primary: var(--primary)`
  - `primary-foreground: var(--primary-foreground)`
  - `accent: var(--accent)`
  - `accent-foreground: var(--accent-foreground)`
  - `success: var(--success)`
  - `warning: var(--warning)`
  - `danger: var(--danger)`
  - `border: var(--border)`
  - `muted: var(--muted)`
  - `muted-foreground: var(--muted-foreground)`
  - `sidebar: var(--sidebar)`
  - `sidebar-foreground: var(--sidebar-foreground)`
- Components should prefer semantic classes such as `bg-background`, `bg-surface`, `text-foreground`, `bg-primary`, `text-primary-foreground`, `border-border`, `text-muted-foreground`, `bg-sidebar`, and `text-sidebar-foreground`.
- Avoid hardcoded repeated hex colors in components/pages. Hex values should live in the CSS variable definitions.
- Do not copy standalone prototype HTML, generated embedded CSS, or bundled scripts.

If implementation uses Tailwind CSS v4-style CSS-first configuration instead of a full config file, document the chosen setup in the implementation PR and keep the same semantic CSS variable contract.

## 4. Design token plan

Create `frontend/src/app/globals.css` with:

- CSS reset/base box sizing.
- Tailwind base import/directives appropriate for the Tailwind version selected during implementation.
- `body` background through `var(--background)`, text through `var(--foreground)`, 14px base font, system/Inter-style font stack.
- Semantic Navy CSS variables:
  - `--background`
  - `--surface`
  - `--foreground`
  - `--primary`
  - `--primary-foreground`
  - `--accent`
  - `--accent-foreground`
  - `--success`
  - `--warning`
  - `--danger`
  - `--border`
  - `--muted`
  - `--muted-foreground`
  - `--sidebar`
  - `--sidebar-foreground`
- Optional supporting CSS variables for hover states, focus ring, spacing, radius, and shadow.
- Accessible `:focus-visible` styles.
- Component styling should primarily live in Tailwind utility classes on local primitives, not global one-off page CSS.

Suggested token values derived from the prototype visual direction:

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

Do not copy the standalone prototype HTML or generated script output.

## 5. shadcn/Radix-style shared UI primitive plan

Use "shadcn/Radix-style" as the architecture direction, not as a CLI requirement.

Shared primitive rules:

- Components live under `frontend/src/shared/ui/` unless they contain product-context behavior or copy.
- Components use Tailwind utility classes backed by semantic CSS variables.
- Components expose small composable APIs and preserve native semantics.
- Components accept `className` overrides for composition.
- Components use variant-based styling for repeated visual states.
- Components use `forwardRef` where appropriate, especially native controls and focusable primitives.
- Components do not duplicate one-off markup across pages.
- Components do not know about IdentityAccess use cases, API errors, tokens, or routing.

Dependency rules:

- Do not require the full shadcn CLI unless the repo explicitly adopts it later.
- Do not add Radix packages for simple local primitives.
- Use local Tailwind components for `Button`, `Input`, `Card`, `Badge`, and `Skeleton`.
- Add Radix only when a component genuinely needs interaction/accessibility behavior that native markup does not handle well, and only after confirming compatibility with the frontend setup.
- Do not add `class-variance-authority`, `clsx`, or `tailwind-merge` by default. Add a small local class helper or variant helper only if repeated variant composition becomes noisy.

Planned primitives:

- `Button`
- `Input`
- `PasswordInput`
- `FormField`
- `Alert`
- `Card`
- `Badge`
- `Skeleton`
- `EmptyState`
- `PageHeader`
- `SidebarNav`
- `AppShell`

## 6. API/auth client plan

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

## 7. Session/auth state plan

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

## 8. Routing/protection plan

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

## 9. Component plan

Button:

- Variants: primary, secondary, ghost, danger.
- Sizes suitable for 14px SaaS density.
- Disabled/loading state.
- Native button semantics preserved.
- Tailwind classes should read from semantic tokens, for example `bg-primary`, `text-primary-foreground`, `border-border`, and `text-muted-foreground`.
- Accept `className`.
- Use `forwardRef<HTMLButtonElement, ...>` if the component wraps a native button.

Input:

- Generic text input with forwarded standard input props.
- Visible focus state, disabled state, invalid state.
- Does not own labels by itself.
- Accept `className`.
- Use `forwardRef<HTMLInputElement, ...>`.

PasswordInput:

- Wraps `Input` with show/hide control.
- Button must have accessible label.
- Does not store password beyond React form state.
- Implement locally with native input/button behavior; no Radix package needed.

FormField:

- Owns label, description/help, error message, and `aria-describedby` wiring.
- Works with `Input` and `PasswordInput`.

Alert:

- Variants: info, success, warning, danger.
- Use `role="alert"` or `aria-live` for form errors and status updates where appropriate.

Card:

- Generic contained surface for auth forms and repeated dashboard placeholders.
- Radius should stay restrained, around 8px.
- Use `bg-surface`, `border-border`, and semantic shadow/radius values.

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
- Use `bg-sidebar` and `text-sidebar-foreground` for dark Navy navigation.

## 10. Page plan

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

## 11. Logout behavior plan

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

## 12. Accessibility checklist

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

## 13. Testing plan

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

## 14. Focused verification commands for later

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

## 15. Open questions/blockers

- Should issue #49 adapt frontend to current backend login response with embedded `account`, or should backend add `/api/auth/me` first in a separate issue?
- Should the current `/dashboard` route redirect to `/app/dashboard` for compatibility, or be removed during implementation?
- Should frontend test tooling be introduced in issue #49, or deferred to keep package/config changes smaller?
- Should Navy tokens live directly on `:root`, or under a `.boardly` root class for future embedding/isolation? Recommended for this product app: `:root`.
- Should logout clear local memory state even if the backend logout request fails? Recommended: yes, with backend logout treated as best effort.
- Should Tailwind be configured with a traditional `tailwind.config.*` theme mapping or a CSS-first setup if the installed Tailwind version supports it? Recommended: choose the minimal setup compatible with the Next.js version at implementation time while preserving the semantic CSS variable contract.
- Should a class composition helper be added? Recommended: defer until repeated primitive variants make it worthwhile.
