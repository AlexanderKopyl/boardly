---
name: frontend-e2e-playwright
description: "Design and review Boardly frontend Playwright E2E and smoke tests for critical user flows: auth/session, protected routes, API integration, navigation, accessibility basics, and regression checks."
---

# Frontend E2E Playwright Skill

Use this skill when a task involves frontend end-to-end tests, browser smoke tests, Playwright, critical user flows, auth/session behavior, protected routes, API integration, navigation, or regression coverage.

Do not use Playwright for every component. Use it for flows where browser behavior matters.

## Source of truth

Always consider:

- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` when auth/session is involved
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` when UI/styling/shared primitives are involved

Use current Playwright docs through Context7 when exact APIs/config are needed.

## When to use Playwright

Use Playwright for:

- login/register/logout smoke flows;
- refresh-session/bootstrap behavior;
- protected route guest/authenticated/loading states;
- retry-on-401 browser behavior when implemented;
- cookie-dependent requests using credentials;
- navigation flows that cross pages/layouts/providers;
- critical form submit flows;
- user-visible regression checks;
- accessibility-sensitive UI primitives when browser/focus behavior matters.

Do not use Playwright for:

- pure domain/application use cases;
- simple mapping functions;
- isolated component logic better covered by unit/component tests;
- backend authorization truth;
- exhaustive visual snapshot testing by default.

## Workflow

1. Identify the user flow and why browser-level coverage is needed.
2. Define test scope: smoke, critical path, regression, or accessibility behavior.
3. Decide whether real backend, mocked API, or seeded test backend is required.
4. Keep backend business truth on the backend; Playwright verifies frontend behavior and integration surface.
5. Avoid storing or printing tokens, cookies, Authorization headers, or secrets in test output.
6. Use stable selectors that reflect accessible UI where possible.
7. Avoid brittle tests based on arbitrary CSS classes or layout details.
8. Define setup/teardown and test data strategy.
9. Add verification commands.
10. Record limitations when tests cannot run locally/CI.

## Recommended file placement

Adapt to actual repository setup.

Preferred:

```text
frontend/playwright.config.ts
frontend/tests/e2e/*.spec.ts
frontend/tests/e2e/fixtures/
frontend/tests/e2e/support/
```

Keep Playwright tests outside product context layers. They are test infrastructure, not application code.

## Test strategy

### Auth/session minimal suite

Recommended smoke coverage:

```text
- guest sees login/register routes
- successful login stores access token in memory behavior only, not persistent storage
- protected route shows loading while bootstrap runs
- protected route redirects/blocks guest after failed bootstrap
- authenticated user can access protected route
- logout clears frontend auth state and calls backend
- refresh failure returns user to guest state
```

### API integration smoke

```text
- frontend sends credentials for cookie-dependent auth requests
- protected request uses Authorization Bearer when access token exists
- repeated 401 does not create infinite refresh loop
- normalized backend errors render safe UI messages
```

### UI/accessibility smoke

```text
- forms have labels
- submit buttons are real buttons
- dialogs/menus focus behavior works when Radix primitives are used
- keyboard navigation works for critical flows
```

## Verification commands

Use package manager that exists in the project.

Examples:

```bash
cd frontend
npm run e2e
npm run e2e:ui
npx playwright test
npx playwright test tests/e2e/auth.spec.ts
npx playwright show-report
```

Do not add or run network/package install commands without explicit permission.

## Output format

## 1. Summary

Short E2E decision.

## 2. Flow under test

What user flow needs browser coverage and why.

## 3. Test type

Smoke / critical path / regression / accessibility behavior.

## 4. Backend/API strategy

Real backend / mocked API / seeded test backend / not applicable.

## 5. Files to create/change

Planned Playwright config/tests/support files.

## 6. Test cases

List scenarios.

## 7. Security notes

Token/cookie/logging constraints.

## 8. Verification commands

Commands to run and expected result.

## 9. Risks and limits

Flakiness, test data, backend dependency, CI setup.

## Boardly rules

- Playwright is for browser-level flows, not every component.
- Backend remains authoritative for permissions, workflow, validation, and business invariants.
- Do not persist access tokens in tests to make flows pass.
- Do not read refresh tokens from JavaScript.
- Do not log tokens, cookies, Authorization headers, or credentials.
- Prefer accessible selectors and user-visible behavior.
- Do not add Playwright dependency/config without explicit implementation scope and verification.
