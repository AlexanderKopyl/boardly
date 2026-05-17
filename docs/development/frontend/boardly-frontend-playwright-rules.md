# Boardly Frontend Playwright Rules

Status: active frontend E2E rulebook  
Scope: Playwright E2E/browser smoke tests for the Boardly Next.js frontend.

## 1. Purpose

Playwright is used for browser-level verification of critical frontend flows.

It is not the default tool for every component, pure function, API mapper, or frontend application use case.

Use Playwright when browser behavior matters: navigation, cookies, auth bootstrap, protected routes, form submission, focus management, and critical user-visible regressions.

This document complements:

```text
docs/development/frontend/boardly-frontend-developer-rules.md
docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md
docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md
docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md
docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md
```

## 2. When to use Playwright

Use Playwright for:

```text
- login/register/logout smoke flows;
- refresh-session/bootstrap behavior;
- protected route guest/authenticated/loading states;
- retry-on-401 browser behavior when implemented;
- cookie-dependent requests using credentials;
- critical navigation flows crossing pages/layouts/providers;
- critical form submit flows;
- user-visible regression checks;
- focus/keyboard behavior for accessibility-sensitive UI primitives.
```

Do not use Playwright for:

```text
- pure domain/application use cases;
- DTO/API contract mapping functions;
- isolated component logic better covered by unit/component tests;
- backend authorization truth;
- exhaustive visual snapshot testing by default;
- checking implementation details like internal React state.
```

## 3. Recommended placement

Adapt to actual frontend setup.

Preferred structure:

```text
frontend/playwright.config.ts
frontend/tests/e2e/*.spec.ts
frontend/tests/e2e/fixtures/
frontend/tests/e2e/support/
```

Rules:

```text
- Keep Playwright tests outside product context layers.
- Do not put Playwright helpers into frontend/src/contexts/*.
- Test helpers may live under frontend/tests/e2e/support.
- Test fixtures must not contain secrets or real credentials.
```

## 4. Auth/session E2E rules

Frontend auth follows ADR-0005.

Access token:

```text
- returned in JSON from login/refresh;
- stored in memory only;
- sent as Authorization: Bearer <token>;
- must not be persisted to localStorage/sessionStorage/IndexedDB/cookies/URL.
```

Refresh token:

```text
- opaque;
- stored in HttpOnly cookie;
- managed by browser;
- never returned in JSON;
- never readable by JavaScript.
```

Playwright tests must not make flows pass by violating these rules.

Forbidden:

```text
- manually reading refresh token from JavaScript;
- storing access token in localStorage just for tests;
- logging tokens/cookies/Authorization headers;
- asserting raw token values;
- using real user credentials in test code.
```

## 5. Minimal auth smoke suite

Recommended first suite:

```text
- guest can view login route;
- successful login reaches authenticated area;
- protected route shows loading/bootstrap state when applicable;
- guest is blocked or redirected after failed bootstrap;
- authenticated user can access protected route;
- logout clears frontend auth state and calls backend;
- refresh failure returns user to guest state;
- access token is not persisted to localStorage/sessionStorage/IndexedDB.
```

## 6. API/browser integration checks

Recommended checks when auth/API client is implemented:

```text
- cookie-dependent auth requests include credentials;
- protected requests use Authorization: Bearer when access token exists;
- repeated 401 does not create infinite refresh loop;
- normalized backend errors render safe UI messages;
- network/server failures show safe fallback UI.
```

Do not rely on random backend message strings for assertions. Prefer stable error codes or user-visible safe messages.

## 7. Selector strategy

Prefer selectors that represent user-visible behavior:

```text
getByRole
getByLabel
getByText when stable
getByPlaceholder when appropriate
```

Use `data-testid` only when accessible selectors are not stable or practical.

Avoid assertions based on:

```text
- arbitrary Tailwind class strings;
- DOM structure that is not part of the user contract;
- internal React component names;
- generated IDs;
- timing assumptions without waiting for UI state.
```

## 8. Test data and backend strategy

Choose one strategy explicitly:

```text
- real test backend with seeded data;
- mocked API at browser/network layer;
- dedicated test fixtures;
- local dev backend for manual smoke only.
```

Rules:

```text
- Do not use production services.
- Do not depend on real external email/payment/third-party services.
- Do not commit real credentials.
- Keep test users deterministic and resettable.
- Make backend dependency explicit in the test command/README.
```

## 9. CI and verification commands

Use the package manager already used by the frontend.

Common commands:

```bash
cd frontend
npm run e2e
npm run e2e:ui
npx playwright test
npx playwright test tests/e2e/auth.spec.ts
npx playwright show-report
```

If Playwright is not installed yet, dependency installation must be a separate explicit implementation task.

Do not run install/browser download commands without approval when dependency changes or network access are required.

## 10. Flakiness rules

Avoid flaky E2E tests.

Rules:

```text
- Prefer auto-waiting Playwright assertions.
- Avoid fixed sleeps/timeouts unless there is no better signal.
- Wait for visible UI state, route state, or network state intentionally.
- Keep each E2E test focused on one user flow.
- Do not create massive end-to-end journeys that fail for many unrelated reasons.
- Record known limitations in task verification artifacts.
```

## 11. Reporting and artifacts

When Playwright is used in a task, record in task artifacts:

```text
- test files created/updated;
- backend/API strategy;
- commands run;
- pass/fail result;
- not-run reason if skipped;
- screenshots/traces only if relevant and safe;
- remaining flakiness or CI risks.
```

Recommended artifact:

```text
<task-folder>/frontend-verification.md
```

or cross-stack:

```text
<task-folder>/verification.md
```

## 12. Common mistakes

Do not:

```text
- add Playwright tests for every component;
- use E2E tests instead of cheaper unit/application tests;
- persist access tokens to simplify tests;
- read HttpOnly refresh cookies from JavaScript;
- assert raw token/cookie values;
- use real credentials or production APIs;
- rely on arbitrary CSS class selectors;
- hide failing E2E tests as manual smoke without explanation;
- add dependency/config/browser installs without explicit task scope.
```

## 13. Final rule

Playwright should protect critical user flows, not replace the frontend test pyramid.

Use the cheapest test that proves the behavior:

```text
pure function -> unit test
frontend use case -> application test with fakes
API mapping -> infrastructure test
component behavior -> component test
browser integration / auth route flow / focus behavior -> Playwright
```
