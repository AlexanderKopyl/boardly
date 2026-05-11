# Boardly Frontend Developer Rules

Status: active frontend rulebook  
Scope: Next.js / TypeScript frontend code in Boardly, especially `frontend/src/app/`, `frontend/src/shared/`, `frontend/src/contexts/`, tests, UI primitives, API gateways, hooks, guards, and frontend configuration.

## 1. Purpose

This document defines production-oriented frontend development rules for Boardly.

Boardly is a Jira-like project and task management system. It is not a CRM.

The goal is not academic purity. The goal is safe, readable, testable frontend code that respects Boardly's API-first direction, frontend context-based hexagonal architecture, authentication security model, and backend ownership of business truth.

This document complements:

```text
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md
frontend/README.md
```

## 2. Baseline TypeScript rules

New TypeScript files must follow these rules:

```text
- Use TypeScript strict mode.
- Use explicit exported types for public contracts.
- Prefer `type` for object/data shapes and unions.
- Use `interface` for ports/contracts intended to be implemented by adapters.
- Avoid `any`.
- Avoid `unknown` leaking past boundaries; narrow it immediately.
- Avoid implicit return types for exported functions.
- Prefer readonly data where mutation is not required.
- Prefer discriminated unions for state machines and error states.
- Do not rely on implicit truthiness for important business/security decisions.
- Do not use magic strings when the value is a shared contract.
```

Allowed local inference:

```ts
const normalizedEmail = email.trim().toLowerCase();
```

Required explicit public contract:

```ts
export type AccountStatus =
  | 'pending_approval'
  | 'active'
  | 'rejected'
  | 'disabled';

export type Account = {
  readonly id: string;
  readonly email: string;
  readonly name: string;
  readonly status: AccountStatus;
};
```

Do not use `any` to bypass API uncertainty. Create a transport contract type and map it explicitly.

## 3. Core frontend architecture rules

Default direction:

```text
app -> context presentation -> context application -> context domain
context infrastructure -> context application ports
shared infrastructure -> context infrastructure
```

Forbidden direction:

```text
domain -> React
domain -> Next.js
domain -> fetch / Axios
domain -> browser storage
domain -> environment variables
application -> React components
application -> raw fetch
application -> localStorage/sessionStorage/cookies
presentation -> raw backend API calls
presentation -> backend URL construction
presentation -> token refresh/retry algorithms
shared -> product contexts
```

Boardly frontend layers:

```text
frontend/src/app
frontend/src/shared
frontend/src/contexts/<Context>/domain
frontend/src/contexts/<Context>/application
frontend/src/contexts/<Context>/infrastructure
frontend/src/contexts/<Context>/presentation
```

## 4. Context and module rules

Frontend product contexts are inspired by bounded contexts, but they are not backend DDD contexts copied into the browser.

Expected contexts include:

```text
identity-access
projects
issues
workflow
boards
collaboration
notifications
search
reporting
audit
integrations
automation
```

Rules:

```text
- Start with `contexts/identity-access` only.
- Do not create empty future contexts.
- Add a context only when a real product slice needs it.
- Do not put context-specific behavior into `shared`.
- Do not deep-import another context's infrastructure or internal files.
- If cross-context usage appears, expose an explicit public contract or move the contract to shared only if it is truly generic.
- Backend remains authoritative for business invariants.
```

Bad:

```text
shared/lib/permissions.ts decides whether user can transition an issue to Done.
```

Good:

```text
issues/presentation renders allowed actions returned by the backend.
Symfony validates the command again when submitted.
```

## 5. Layer responsibilities

### app

`frontend/src/app` owns Next.js routing, layouts, and composition.

Allowed:

```text
layout.tsx
providers.tsx
login/page.tsx
register/page.tsx
dashboard/page.tsx
```

Rules:

```text
- Pages compose context presentation components.
- Pages may pass route params/search params as UI input.
- Pages must not contain raw API calls.
- Pages must not normalize backend errors.
- Pages must not own token refresh behavior.
- Pages must not contain product business decisions.
```

### shared

`frontend/src/shared` owns generic frontend infrastructure and UI primitives.

Allowed:

```text
shared/config/env.ts
shared/api/http-client.ts
shared/api/api-error.ts
shared/ui/Button.tsx
shared/ui/Input.tsx
shared/lib/cn.ts
shared/theme/tokens.css
```

Rules:

```text
- Shared code must not depend on product contexts.
- Shared UI components must be generic.
- Shared API infrastructure may know HTTP mechanics.
- Shared API infrastructure must not know product use cases.
```

### domain

Frontend `domain` contains frontend-level types and pure models.

Allowed:

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

```text
- No React imports.
- No Next.js imports.
- No HTTP client imports.
- No browser storage access.
- No environment variable access.
- No backend persistence assumptions.
- No authoritative business rules that belong to Symfony.
```

### application

Frontend `application` contains use cases and ports.

Allowed:

```text
login
register
refreshSession
logout
bootstrapSession
loadProjectBoard
```

Rules:

```text
- Use cases orchestrate frontend flows.
- Use cases depend on ports/interfaces.
- Use cases do not import React components.
- Use cases do not call raw `fetch` directly.
- Use cases do not read storage, cookies, or environment variables directly.
- Use cases return explicit results or throw controlled frontend application errors.
```

### infrastructure

Frontend `infrastructure` contains adapters.

Allowed:

```text
auth-http-gateway.ts
auth-api-contracts.ts
auth-memory-store.ts
project-http-gateway.ts
```

Rules:

```text
- HTTP adapters translate backend API contracts into frontend models.
- State adapters implement application ports.
- Infrastructure may depend on `shared/api` and `shared/config`.
- Infrastructure must hide transport details from presentation.
- Infrastructure must not leak raw response shapes into UI.
```

### presentation

Frontend `presentation` contains React integration.

Allowed:

```text
LoginForm.tsx
RegisterForm.tsx
LogoutButton.tsx
ProtectedRoute.tsx
useAuth.ts
ProjectSidebar.tsx
IssueDetailsPanel.tsx
```

Rules:

```text
- Components render UI state.
- Components may own local form state.
- Components call use cases through hooks/providers.
- Components do not call raw backend APIs directly.
- Components do not decide backend permissions/workflow truth.
- Components do not contain refresh-token or retry algorithms.
```

## 6. File naming and export rules

Use consistent file naming:

```text
- React components: PascalCase.tsx
- hooks: useSomething.ts
- use cases: kebab-case.ts or precise verb names matching existing context style
- domain types: kebab-case.ts
- ports: kebab-case.ts
- infrastructure adapters: kebab-case.ts
```

Examples:

```text
presentation/ui/LoginForm.tsx
presentation/hooks/useAuth.ts
application/use-cases/login.ts
application/ports/auth-gateway.ts
infrastructure/http/auth-http-gateway.ts
domain/auth-session.ts
```

Rules:

```text
- Prefer named exports.
- Avoid default exports except where Next.js requires them for pages/layouts.
- Avoid barrel files until there is a real import-stability reason.
- Do not create `index.ts` files that hide dependency direction problems.
```

## 7. Naming rules

Names must describe responsibility.

Good names:

```text
AuthGateway
AuthSessionStore
AuthHttpGateway
AuthMemoryStore
login
refreshSession
bootstrapSession
ProtectedRoute
LoginForm
```

Weak names:

```text
ApiService
AuthService
FrontendManager
DataProvider
Helper
Utils
Common
MainHook
```

Rules:

```text
- Use `Gateway` for infrastructure-facing ports that call backend APIs.
- Use `Store` for state/session abstractions.
- Use verbs for use cases.
- Use React component names that describe visible UI responsibility.
- Avoid generic names unless the code is truly generic and small.
```

## 8. API client and HTTP rules

Create one shared HTTP client foundation.

Rules:

```text
- Do not scatter raw `fetch` calls across components.
- Do not construct backend URLs inside React components.
- Do not parse backend error responses in components.
- Normalize API errors centrally.
- All auth/cookie-dependent requests must use `credentials: 'include'`.
- Protected API requests use `Authorization: Bearer <accessToken>`.
- Retry original request once after successful refresh.
- Never infinite-loop on repeated 401 responses.
```

Allowed flow:

```text
presentation -> application use case -> AuthGateway port -> AuthHttpGateway -> shared http client -> Symfony API
```

Bad:

```tsx
await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/auth/login`, {
  method: 'POST',
  body: JSON.stringify(form),
});
```

Good:

```ts
await login({
  email,
  plainPassword,
  gateway: authGateway,
  sessionStore,
});
```

## 9. API contract mapping rules

Backend response shapes are transport contracts, not frontend domain models.

Rules:

```text
- Define API contract types in `infrastructure/http/*-api-contracts.ts`.
- Map transport contracts into frontend domain/application models.
- Do not leak raw API response objects into presentation.
- Do not branch UI logic on random backend message strings.
- Prefer stable backend error codes.
```

Example:

```ts
export type LoginResponseContract = {
  readonly accessToken: string;
  readonly tokenType: 'Bearer';
  readonly expiresIn: number;
  readonly account: AccountContract;
};
```

## 10. Authentication rules

Frontend auth model follows ADR-0005.

Access token:

```text
- returned in JSON from login/refresh;
- stored in memory only;
- sent as Authorization: Bearer <token>;
- lost after browser reload by design.
```

Refresh token:

```text
- opaque;
- stored in HttpOnly cookie;
- managed by browser;
- never returned in JSON;
- never readable by JavaScript.
```

Forbidden token storage:

```text
localStorage
sessionStorage
IndexedDB
JavaScript-readable cookies
URL params
persisted Zustand/Redux storage
NEXT_PUBLIC_* environment variables
```

Rules:

```text
- Do not manually read or attach refresh tokens.
- Do not log access tokens, refresh tokens, cookies, or Authorization headers.
- Do not expose token values in React DevTools through persisted state.
- Do not make refresh bootstrap instantly redirect before it finishes.
- Protected routes must handle loading/authenticated/guest states.
- Logout must call backend and clear local auth state.
```

## 11. State management rules

Start with the smallest state mechanism that preserves auth security and testability.

Rules:

```text
- Auth session state must be memory-only.
- Do not persist auth state.
- Do not introduce global state libraries before a concrete need exists.
- Server state from APIs should not be copied into local state unless needed for UI interaction.
- Keep form state local to forms unless shared state is justified.
- Use explicit state unions for async flows.
```

Good:

```ts
export type AuthState =
  | { readonly status: 'bootstrapping' }
  | { readonly status: 'guest' }
  | { readonly status: 'authenticated'; readonly session: AuthSession };
```

Bad:

```ts
export type AuthState = {
  isLoggedIn: boolean;
  user?: Account;
  token?: string;
  loading?: boolean;
};
```

## 12. Error handling rules

Errors must be explicit and safe for UI.

Expected categories:

```text
validation
authentication
authorization
not_found
conflict
rate_limit
server_error
network_error
unknown
```

Rules:

```text
- Normalize backend errors in shared API/infrastructure.
- Do not show raw stack traces.
- Do not show raw exception class names.
- Do not rely on random error message strings.
- Do not leak sensitive request data into logs/errors.
- Components should render safe user-facing messages.
```

Standard frontend shape:

```ts
export type ApiError = {
  readonly category: ApiErrorCategory;
  readonly code: string;
  readonly message: string;
  readonly status?: number;
  readonly details?: unknown;
};
```

## 13. Environment config rules

Use public environment variables only for public browser-safe values.

Allowed:

```text
NEXT_PUBLIC_API_URL=http://127.0.0.1:8080
```

Forbidden:

```text
JWT secrets
refresh token secrets
database credentials
private backend config
service credentials
admin bootstrap credentials
```

Rules:

```text
- Read env in `shared/config/env.ts`.
- Validate required public env at startup/runtime boundary.
- Do not read `process.env` throughout the codebase.
- Do not store tokens or secrets in env intended for browser bundles.
```

## 14. UI component rules

Shared UI primitives live in:

```text
frontend/src/shared/ui/
```

Rules:

```text
- Shared UI components must be generic.
- Context-specific UI stays in the context presentation layer.
- Shared UI must not import product contexts.
- Prefer composition over large prop-heavy components.
- Keep accessibility requirements visible in component APIs.
- Do not build a design system before repeated needs appear.
```

Good shared components:

```text
Button
Input
Textarea
Dialog
DropdownMenu
Badge
Spinner
FormField
```

Bad shared components:

```text
IssueStatusButton in shared/ui
ProjectPermissionSelect in shared/ui
AccountApprovalCard in shared/ui
```

## 15. Styling rules

Preferred direction should be documented by a separate styling/UI ADR before broad implementation.

Until that ADR exists, follow these constraints:

```text
- Keep styles close to UI components.
- Do not mix multiple styling systems without an explicit decision.
- Do not introduce a heavy component library casually.
- Do not write page-specific global CSS except for reset, theme tokens, and base layout.
- Prefer design tokens for colors, spacing, radii, typography, and shadows.
- Use semantic token names, not raw product meanings everywhere.
```

Good token names:

```text
--color-background
--color-foreground
--color-primary
--color-primary-foreground
--color-border
--radius-md
```

Weak token names:

```text
--random-blue
--button-color-1
--jira-card-bg-copy
```

If Tailwind/shadcn/Radix is adopted later, keep generated/customized primitives in `shared/ui` and do not scatter one-off UI patterns across contexts.

## 16. Accessibility rules

Boardly is a productivity application. Keyboard and screen-reader basics matter.

Rules:

```text
- Forms must have associated labels.
- Buttons must be real buttons, not clickable divs.
- Interactive elements must be keyboard-accessible.
- Dialogs/menus/popovers must manage focus correctly.
- Loading states must not trap the user without feedback.
- Error messages must be visible and associated with inputs where possible.
- Color must not be the only way to communicate state.
```

## 17. Form rules

Forms may own UI state, but not backend flow complexity.

Rules:

```text
- Keep simple form state local.
- Do not put token/session logic in forms.
- Do not call raw APIs from forms.
- Use frontend validation for UX only.
- Backend remains authoritative for validation and business decisions.
- Keep backend request field names aligned with API contract.
```

IdentityAccess reminder:

```json
{
  "plainPassword": "..."
}
```

Do not silently rename API contract fields to `password` unless the backend contract changes.

## 18. Security and privacy rules

Frontend code must be conservative around sensitive data.

Do not log:

```text
plain passwords
access tokens
refresh tokens
cookies
Authorization headers
full credential request bodies
private backend URLs with secrets
```

Rules:

```text
- Do not expose secrets through `NEXT_PUBLIC_*`.
- Do not persist credentials or tokens.
- Do not put auth tokens in query strings.
- Do not implement frontend-only permission protection as security.
- Backend must validate all protected actions.
- Frontend route guards are UX, not security boundaries.
```

## 19. Performance rules

Start simple, but avoid obvious product UI bottlenecks.

Rules:

```text
- Do not fetch the same data repeatedly from many components.
- Do not store huge API responses in global state by default.
- Avoid unnecessary client components in Next.js.
- Use client components only when interactivity requires them.
- Keep expensive derived data memoized only when there is measured need.
- Avoid premature virtualization until lists become large enough to need it.
```

For future boards/issues:

```text
- Large boards/lists may require pagination, virtualization, or backend-optimized read models.
- Drag-and-drop UI must handle backend conflicts and failed transitions explicitly.
```

## 20. Testing rules

Tests must match the layer being tested.

Domain tests:

```text
- pure TypeScript tests;
- no React;
- no Next.js;
- no HTTP;
- assert small pure rules and state transitions.
```

Application tests:

```text
- use fake gateways/stores;
- assert orchestration;
- assert session state changes;
- assert error paths;
- no real HTTP.
```

Infrastructure tests:

```text
- test API contract mapping;
- test HTTP gateway behavior with mocked fetch/client;
- test normalized errors;
- test refresh retry behavior when implemented.
```

Presentation tests:

```text
- test visible behavior;
- test form interaction;
- test protected route states;
- avoid asserting implementation details.
```

Required auth test coverage:

```text
login success
login failure
register success
register failure
refresh on bootstrap
refresh failure
logout
protected route loading/authenticated/guest states
retry once after 401
no infinite loop on repeated 401
no persisted token storage
```

## 21. Verification commands

Use frontend package scripts.

Common checks from `frontend/`:

```bash
npm install
npm run typecheck
npm run lint
npm run build
npm run dev
```

If another package manager is accepted, use equivalent commands and commit the correct lock file.

Before adding/upgrading frontend stack packages, verify current latest stable versions:

```bash
npm view next version
npm view react version
npm view react-dom version
npm view typescript version
npm view eslint version
npm view @types/react version
npm view @types/node version
```

Do not copy outdated package versions from old examples.

## 22. PR / task summary template

Every frontend change summary should include:

```text
Changed:
- <file/path.tsx>: <what changed and why>

Architecture:
- Context boundary respected: yes/no + note
- app layer kept to routing/composition: yes/no/not applicable
- presentation has no raw API calls: yes/no/not applicable
- application depends on ports: yes/no/not applicable
- infrastructure details isolated: yes/no/not applicable
- shared code remains context-free: yes/no/not applicable
- auth/token storage rules respected: yes/no/not applicable

Verification:
- npm run typecheck: pass/fail/not run
- npm run lint: pass/fail/not run
- npm run build: pass/fail/not run
- tests: pass/fail/not run
- manual smoke: <route/action/result>

Security:
- no token persistence: yes/no/not applicable
- no secrets exposed through NEXT_PUBLIC_*: yes/no/not applicable
- no sensitive data logged: yes/no/not applicable

Rollback:
- <exact revert/config rollback>
```

## 23. Common mistakes to avoid

Do not:

```text
- put all frontend code into components/services/utils/hooks;
- put context-specific code into shared;
- make React components call raw backend APIs;
- build backend URLs in components;
- store access tokens in localStorage/sessionStorage/IndexedDB;
- read refresh tokens from JavaScript;
- persist auth state;
- redirect protected routes before refresh bootstrap completes;
- duplicate backend permissions/workflow as frontend truth;
- use backend message strings as UI branching logic;
- expose backend secrets through NEXT_PUBLIC_*;
- introduce a global God api.ts;
- introduce a global God auth.ts;
- add empty future contexts without implementation need;
- hide dependency direction problems behind barrel files;
- add heavy libraries without a documented reason;
- log credentials, tokens, cookies, or Authorization headers.
```

## 24. Current IdentityAccess contract reminders

Auth endpoints:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
GET  /api/auth/me
```

Register/login request field:

```json
{
  "plainPassword": "..."
}
```

Auth model:

```text
access token  -> JWT returned in JSON, stored by frontend in memory
refresh token -> opaque HttpOnly cookie, never returned in JSON
```

Frontend auth requests must use credentials:

```ts
fetch(url, {
  credentials: 'include',
});
```

Protected API requests use:

```http
Authorization: Bearer <accessToken>
```

## 25. Final rule

When unsure, prefer the smaller explicit design that preserves boundaries and can be tested.

Do not add abstractions, contexts, providers, stores, UI primitives, or styling systems just because the pattern exists elsewhere. Add them when the current frontend slice needs them and the responsibility is clear.
