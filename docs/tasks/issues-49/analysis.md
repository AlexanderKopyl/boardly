# Issue 49 Analysis: Auth Screens and First App Shell

## 1. MemPalace findings

Relevant project memory says the first frontend milestone should start with the IdentityAccess context and avoid Projects, Issues, Boards, Workflow, Reporting, and Admin UI until authentication is stable.

Key memory points:

- Boardly frontend direction is Next.js + TypeScript, backed by the Symfony API.
- Frontend source should follow context-based hexagonal architecture from ADR-0006: `app/` for routing/composition, `shared/` for generic primitives/infrastructure, and `contexts/identity-access/` for auth domain/application/infrastructure/presentation.
- Access token is returned by login/refresh and must be held in memory only.
- Refresh token is an opaque HttpOnly cookie managed by the browser; JavaScript must never read, store, or attach it.
- Cookie-dependent requests must use `credentials: 'include'`.
- Refresh and logout require `X-CSRF-Intent: auth-refresh`.
- Registration creates a `pending_approval` account and does not create a session.
- Frontend bootstrap should call refresh after reload, then restore the in-memory session if refresh succeeds.
- Raw API calls must not live in React components.
- Testing should cover auth use cases, gateway contract mapping, memory-only storage, bootstrap behavior, logout clearing, protected-route behavior, and safe error display.

Memory is context only. The current code/docs/tests below are stronger evidence where they differ.

## 2. Current repository frontend facts

Frontend workspace:

- There is no root `package.json`; the active frontend workspace metadata is `frontend/package.json`.
- `frontend/package.json` defines `boardly-frontend` with Next.js `16.2.6`, React `19.2.6`, TypeScript `6.0.3`, npm scripts `dev`, `build`, `start`, `lint`, and `typecheck`.
- `frontend/tsconfig.json` enables strict TypeScript and maps `@/*` to `frontend/src/*`.
- `frontend/README.md` and `docs/development/frontend.md` document the accepted frontend structure and auth rules.

Routing and app composition:

- `frontend/src/app/layout.tsx` renders `<Providers>{children}</Providers>` with no global stylesheet imported.
- `frontend/src/app/providers.tsx` is currently an empty client provider wrapper.
- `frontend/src/app/page.tsx` redirects `/` to `/login`.
- `frontend/src/app/login/page.tsx` renders a plain `<main>`, `<h1>Login</h1>`, and `LoginForm`.
- `frontend/src/app/register/page.tsx` renders a plain `<main>`, `<h1>Register</h1>`, and `RegisterForm`.
- `frontend/src/app/dashboard/page.tsx` exists, but issue #49 wants `/app/dashboard`. Current dashboard route wraps a placeholder with `ProtectedRoute` and `LogoutButton`.
- There is no current `frontend/src/app/pending-approval/page.tsx`.
- There is no current `frontend/src/app/auth/session-loading/page.tsx`.
- There is no current `frontend/src/app/app/dashboard/page.tsx`.

Shared frontend infrastructure:

- `frontend/src/shared/config/env.ts` requires `NEXT_PUBLIC_API_URL`.
- `frontend/src/shared/api/http-client.ts` centralizes `fetch`, always sends `credentials: 'include'`, can attach `Authorization: Bearer <accessToken>`, normalizes backend errors into `ApiError`, and supports one 401 retry through `onUnauthorized`.
- `frontend/src/shared/api/api-error.ts` defines `ApiError`.
- `frontend/src/shared/ui/Button.tsx` and `frontend/src/shared/ui/Input.tsx` are minimal unstyled primitives.

IdentityAccess frontend context:

- Domain models exist in `frontend/src/contexts/identity-access/domain/`.
- Application ports/use cases exist in `frontend/src/contexts/identity-access/application/`.
- HTTP and memory-state adapters exist in `frontend/src/contexts/identity-access/infrastructure/`.
- Presentation hooks, forms, guard, and logout button exist in `frontend/src/contexts/identity-access/presentation/`.

Current auth UI:

- `LoginForm` posts `email` and `plainPassword` through `useAuth().login`, then routes to `/dashboard`.
- `RegisterForm` posts `name`, `email`, and `plainPassword`, then shows inline pending text.
- `LogoutButton` calls `logout()` and routes to `/login`.
- `ProtectedRoute` calls `bootstrap()` in an effect, shows `Loading...`, and redirects to `/login` if no session.

Styling/design:

- Tailwind CSS is not currently installed in `frontend/package.json`.
- No Tailwind config file was found under the repo or `frontend/`.
- No PostCSS config file was found under the repo or `frontend/`. `postcss` appears only as a transitive dependency in `frontend/package-lock.json`, not as an explicit frontend styling setup.
- No Vite config exists; the frontend is a Next.js App Router app, not a Vite app.
- No CSS/theme/token file currently exists under `frontend/src`.
- `frontend/src/app/layout.tsx` does not import a global CSS entrypoint.
- No global CSS entrypoint currently exists at `frontend/src/app/globals.css`.
- No existing CSS custom properties for Boardly app tokens were found in frontend source.
- No `components.json`, shadcn CLI setup, `@radix-ui/*`, `class-variance-authority`, `clsx`, or `tailwind-merge` dependency is present in `frontend/package.json`.
- Existing `Button` and `Input` are local shared UI primitives with no Tailwind classes, variant system, `className` override, or `forwardRef`.
- The standalone prototype at `docs/design/boardly/Boardly Prototype _standalone_.html` includes Navy tokens such as `--bd-primary: #1D4ED8`, `--bd-accent: #38BDF8`, `--bd-bg: #F6F8FB`, `--bd-surface: #FFFFFF`, `--bd-text: #0B1220`, status colors, 14px base text, 4px spacing scale, and dark sidebar tokens.
- The prototype is generated standalone HTML with embedded bundled assets/scripts and must remain a visual reference only.

Frontend tests:

- No frontend unit/component/e2e test framework is configured in `frontend/package.json`.
- No frontend test files were found under `frontend/src`.
- Existing verification support is limited to TypeScript, ESLint, and Next build scripts.

## 3. Framework/runtime

The frontend is a Next.js App Router application using:

- Next.js `16.2.6`
- React `19.2.6`
- TypeScript `6.0.3`
- npm package manager

The implementation should use App Router routes under `frontend/src/app/` and client components only where interaction/session state requires them.

## 4. Existing routing/state/API/component conventions

Routing:

- `frontend/src/app/` owns routes and page composition.
- Existing pages import presentation components from `contexts/identity-access`.
- Pages are currently simple server components unless they import client presentation components.

State:

- `AuthMemoryStore` keeps `AuthSession | null` in a module-level variable.
- `useAuth` creates singleton gateway/store instances and mirrors store state into React state per hook instance.
- There is no shared React auth context provider yet, so multiple `useAuth()` consumers can have independent React state even though they share the same memory store.

API:

- Raw `fetch` is centralized in `shared/api/http-client.ts`.
- `AuthHttpGateway` maps auth use cases to backend endpoints.
- Refresh/logout already include `X-CSRF-Intent: auth-refresh`.
- Login/register payloads already use `plainPassword`.

Components:

- Shared UI primitives are intentionally small and context-free.
- IdentityAccess forms own display state and call `useAuth`.
- Current UI is unstyled and lacks labels, field help/error primitives, layout primitives, and app shell primitives.

## 5. Safe auth request implementation

Auth requests should continue to flow through `AuthHttpGateway` and `httpRequest`.

Rules:

- `POST /api/auth/register` body must use `{ email, plainPassword, name }`.
- `POST /api/auth/login` body must use `{ email, plainPassword }`.
- `POST /api/auth/refresh` should send no body and must include `X-CSRF-Intent: auth-refresh`.
- `POST /api/auth/logout` should send no body and must include `X-CSRF-Intent: auth-refresh`.
- All requests must use `credentials: 'include'`; the current HTTP client already does this.
- Protected requests must send `Authorization: Bearer <accessToken>`.
- The frontend must not log, display, store, or expose raw passwords, access tokens, refresh tokens, or cookie values.

Contract mismatch to resolve during implementation:

- Current backend login response includes `account`; frontend `LoginResponse` ignores it and calls `/api/auth/me`.
- `/api/auth/me` is documented in ADR/design docs but no backend controller exists under `src/`.
- Current backend register response returns `accountId` and `status`; frontend `RegisterResponse` expects `id`, `email`, `name`, and `status`.

For issue #49, the frontend should map to the current backend code/tests unless backend implementation is changed in a separate allowed task.

## 6. Refresh/session bootstrap without refresh-token storage

Expected bootstrap behavior:

1. Browser reload loses the in-memory access token by design.
2. Frontend enters a session loading state.
3. Frontend calls `POST /api/auth/refresh` with `credentials: 'include'` and `X-CSRF-Intent: auth-refresh`.
4. Browser automatically sends the HttpOnly `refresh_token` cookie if present.
5. If refresh succeeds, store the returned access token in `AuthMemoryStore` only and build the session from returned/mapped account data.
6. If refresh fails with `invalid_refresh_token` or an unauthenticated 401, clear memory state and route to `/login`.
7. If refresh fails because of network/server error, show safe user-facing feedback without revealing backend internals.

The frontend must never read `document.cookie` for the refresh token and must never persist access token to `localStorage`, `sessionStorage`, IndexedDB, readable cookies, URL params, persisted React state libraries, or env vars.

## 7. Tailwind and Navy design token facts

Current Tailwind state:

- Tailwind is not installed or configured yet.
- There is no `tailwind.config.*`.
- There is no `postcss.config.*`.
- There is no `@tailwind` or Tailwind CSS v4 `@import "tailwindcss"` entrypoint.
- No frontend source file currently uses Tailwind utility classes.

Required styling direction for issue #49 implementation:

- Use Tailwind CSS as the styling utility layer.
- Centralize Navy design tokens as CSS variables instead of repeating hardcoded hex values in pages/components.
- Expose semantic token names suitable for shared primitives and page composition.
- Prefer local reusable primitives with Tailwind classes for simple components.
- Do not copy standalone prototype HTML, bundled script output, or generated prototype CSS wholesale.

Because no Tailwind setup exists today, the later implementation should include the minimal Tailwind setup needed for the current Next.js version and package manager. That means adding the required Tailwind/PostCSS packages and config only in the implementation task, not in this planning-only task. If a Tailwind config is introduced, it should map semantic theme colors to CSS variables so components use classes such as `bg-background`, `text-foreground`, `bg-primary`, `text-primary-foreground`, `border-border`, and `bg-sidebar` rather than repeated hex values.

## 8. Navy design token location

The repo currently has no global stylesheet. The implementation should introduce Navy tokens in a frontend-owned global stylesheet, likely:

```text
frontend/src/app/globals.css
```

Then import it from:

```text
frontend/src/app/layout.tsx
```

Tokens should be CSS custom properties on `:root` or a `.boardly` root class. Because this app is the product UI, `:root` is simpler unless there is a known need to embed multiple design systems on the same page.

Use semantic token names that Tailwind and primitives can share:

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

Additional implementation-only variables may be added for hover states, focus ring, radius, spacing, and shadows, but components should depend first on the semantic tokens above.

Use the prototype values as token references, not copied standalone HTML:

- `--bd-primary: #1D4ED8`
- `--bd-accent: #38BDF8`
- `--bd-bg: #F6F8FB`
- `--bd-surface: #FFFFFF`
- `--bd-text: #0B1220`
- `--bd-success: #16A34A`
- `--bd-warning: #F59E0B`
- `--bd-danger: #EF4444`
- dark Navy sidebar tokens
- 14px base body text
- comfortable SaaS spacing

## 9. shadcn/Radix-style primitive facts and direction

Current shadcn/Radix state:

- The repo does not currently use shadcn CLI artifacts.
- No Radix packages are installed.
- No variant helper dependencies such as `class-variance-authority`, `clsx`, or `tailwind-merge` are installed.
- Existing shared primitives are local files under `frontend/src/shared/ui/`.

Implementation direction:

- Use "shadcn/Radix-style" as a component architecture direction, not as a requirement to install the shadcn CLI.
- Build composable local primitives with accessible markup, Tailwind classes, variant-based styling, `className` override support, and `forwardRef` where the component wraps a native DOM element.
- Do not add Radix packages for simple primitives that can be implemented with native accessible markup.
- Consider Radix only for interaction/accessibility-heavy components if the component genuinely needs Radix behavior and the dependency is compatible with the project setup.
- For this milestone, `Button`, `Input`, `Card`, `Badge`, and `Skeleton` should remain local primitives with Tailwind classes.

## 10. Reusable UI primitives needed

Existing:

- `Button`
- `Input`

Needed for this milestone:

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

These should stay generic under `frontend/src/shared/ui/` unless they contain IdentityAccess-specific copy or behavior.

## 11. Gap analysis by screen/interaction

Login:

- Current route exists at `/login`.
- Needs Navy visual treatment, labeled fields, safe error mapping, pending-approval redirect/CTA for `account_not_active`, and destination update from `/dashboard` to `/app/dashboard`.
- Should avoid calling non-existent `/api/auth/me`; use backend login `account` if current contract remains.

Register:

- Current route exists at `/register`.
- Needs visual treatment, labeled fields, better validation/help, safe duplicate/validation error display, and redirect or link to `/pending-approval` after success.
- Must map current register response `accountId/status`, not stale `id/email/name`.

Pending approval:

- Missing route.
- Should be a simple public/auth-adjacent page explaining that the account is waiting for admin approval, with a path back to login.
- Must not implement admin approval UI.

Session loading:

- Missing route.
- Current protected route renders inline `Loading...`.
- Needs `/auth/session-loading` route or equivalent composition point for session bootstrap/loading state.

App shell:

- Missing reusable shell.
- Current dashboard is a plain page.
- Needs dark Navy sidebar, top/header content area, accessible navigation, account area/logout action, and responsive behavior.
- Navigation should show only MVP placeholders and must not implement detailed Projects/Boards/Tasks/Admin UI.

Dashboard:

- Current route is `/dashboard`; required route is `/app/dashboard`.
- Current content is a placeholder.
- Needs first authenticated dashboard placeholder with standard SaaS density and no future issue-management surface.

Logout:

- Current button calls logout and routes to `/login`.
- Needs disabled/loading/error behavior.
- Local memory state should clear even when backend logout fails or after a best-effort attempt, depending on final policy.
- Must not reveal refresh-token validity.

## 12. Risks and constraints

- `plainPassword` is required by backend request DTOs; using `password` would fail.
- `credentials: 'include'` is required so the browser sends/receives the refresh cookie.
- Access token must remain memory-only.
- Refresh token must remain HttpOnly-cookie-only and unreadable by frontend code.
- Tailwind is not installed/configured yet; issue #49 implementation must either add the minimal Tailwind setup or explicitly block on a separate styling setup issue.
- Do not hardcode repeated Navy hex colors in pages/components; use CSS variables and Tailwind theme mappings once Tailwind is configured.
- Do not require the full shadcn CLI unless the repo explicitly adopts it later.
- Do not add Radix packages unless a component genuinely needs Radix behavior.
- Current frontend depends on `/api/auth/me`, but backend code does not implement it.
- Current backend register response shape differs from `docs/development/frontend.md` and current frontend types.
- Current backend login response includes account data, which can reduce or remove the need for `/api/auth/me` in this milestone.
- `useAuth` is not a real shared React context; multiple hook consumers can drift in rendered state.
- No frontend test framework currently exists; adding one would require package/config changes and should be a separate implementation decision.
- User-facing errors must be safe: invalid credentials should not reveal account existence; pending/rejected/disabled should use backend stable codes without exposing sensitive internals.
- Do not overbuild Projects, Boards, Tasks, Reports, Admin approval, analytics, automation, or future issue-management UI.
- Accessibility must be part of the first UI pass: semantic landmarks, labels, focus states, keyboard operation, color contrast, `aria-live` for errors/loading, and no text overlap at mobile widths.

## Conflicts between memory/docs and current code

- Memory/docs mention `/api/auth/me`; current backend source has no `/api/auth/me` controller.
- `docs/development/frontend.md` says register returns `id`, `email`, `name`, `status`; current backend controller/tests return only `accountId` and `status`.
- Current frontend route is `/dashboard`; issue #49 requires `/app/dashboard`.
- Current frontend has no global token stylesheet even though prototype/docs define Navy tokens.
- Current frontend has auth use cases/forms already, while earlier memory describes them as an initial skeleton target.
