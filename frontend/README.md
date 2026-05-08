# Boardly Frontend

## Current frontend context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

The repository keeps the Symfony backend at the repository root. The frontend workspace lives under:

```text
frontend/
```

Accepted architecture decisions define the frontend/backend direction:

- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`

The backend remains the source of truth for identity, permissions, workflow rules, project state, task state, and persistence.

## Architectural assessment

The frontend must not start from project/task screens.

The first frontend milestone is authentication integration because protected product UI depends on a stable session model.

Initial constraints:

- Next.js is the primary product UI direction.
- TypeScript is required.
- Symfony exposes the API.
- Frontend source code follows context-based hexagonal architecture.
- Access token is returned in JSON and stored in memory only.
- Refresh token is an opaque HttpOnly cookie managed by the browser.
- Refresh token must never be read by JavaScript.
- Refresh happens through `POST /api/auth/refresh`.
- Logout happens through `POST /api/auth/logout`.
- Auth requests must include browser credentials.

## Recommended direction

Start with a small vertical IdentityAccess slice:

1. Next.js + TypeScript skeleton.
2. Frontend environment config.
3. Shared HTTP client and normalized API errors.
4. IdentityAccess domain models.
5. IdentityAccess application ports.
6. IdentityAccess HTTP gateway.
7. Memory-only auth session store.
8. Login use case and UI.
9. Register use case and UI.
10. Refresh-on-bootstrap use case.
11. Logout use case and UI action.
12. Protected route guard.
13. Basic authenticated dashboard placeholder.

Do not implement Project, Issue, Board, Workflow, or Reporting UI before the authentication flow is stable.

## Target source structure

```text
frontend/
  src/
    app/
      layout.tsx
      providers.tsx
      page.tsx
      login/
        page.tsx
      register/
        page.tsx
      dashboard/
        page.tsx

    shared/
      config/
        env.ts
      api/
        http-client.ts
        api-error.ts
      ui/

    contexts/
      identity-access/
        domain/
          account.ts
          account-status.ts
          auth-session.ts
          auth-error.ts

        application/
          ports/
            auth-gateway.ts
            auth-session-store.ts
          use-cases/
            login.ts
            register.ts
            refresh-session.ts
            logout.ts
            bootstrap-session.ts

        infrastructure/
          http/
            auth-http-gateway.ts
            auth-api-contracts.ts
          state/
            auth-memory-store.ts

        presentation/
          ui/
            LoginForm.tsx
            RegisterForm.tsx
            LogoutButton.tsx
          guards/
            ProtectedRoute.tsx
          hooks/
            useAuth.ts
```

## Layer rules

### app

Next.js routing and page composition only.

Pages compose context presentation components. They must not contain raw API calls, backend URL construction, error normalization, token refresh logic, or business rules.

### shared

Truly shared frontend infrastructure and UI primitives only.

Allowed examples:

```text
shared/config/env.ts
shared/api/http-client.ts
shared/api/api-error.ts
shared/ui/Button.tsx
shared/ui/Input.tsx
```

Shared code must not depend on product contexts.

### contexts

Product-specific frontend modules.

Each context may contain:

```text
domain/
application/
infrastructure/
presentation/
```

Do not create empty future contexts. Add `projects`, `issues`, `boards`, `workflow`, or `notifications` only when a real implementation slice requires them.

### domain

Frontend-level models and pure types.

These are not backend Doctrine entities and not backend aggregates.

Frontend domain must not contain authoritative backend business invariants.

### application

Frontend use cases and ports.

Use cases orchestrate browser-side flows and depend on interfaces, not on React, Next.js, raw `fetch`, cookies, storage, or environment variables.

### infrastructure

Adapters for HTTP, state, and external mechanisms.

HTTP gateways translate backend API contracts into frontend models. State adapters implement memory-only session storage where required.

### presentation

React components, hooks, guards, and form UI.

Presentation calls use cases or context hooks. It does not own token refresh algorithms, retry behavior, backend business decisions, or API error normalization.

## API integration rules

All auth and cookie-dependent requests must use credentials.

For `fetch`:

```ts
fetch(url, {
  credentials: 'include',
});
```

For Axios, if Axios is adopted later:

```ts
axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
});
```

The frontend must never manually read, store, or attach the refresh token.

The access token may be attached as:

```http
Authorization: Bearer <accessToken>
```

The access token storage must be memory-only.

Forbidden storage locations:

- `localStorage`
- `sessionStorage`
- `IndexedDB`
- persisted Zustand/Redux storage
- URL params
- JavaScript-readable cookies
- `NEXT_PUBLIC_*` environment variables

## Files likely affected next

Expected first implementation files:

```text
frontend/package.json
frontend/tsconfig.json
frontend/next.config.ts
frontend/.env.example
frontend/src/app/layout.tsx
frontend/src/app/providers.tsx
frontend/src/app/page.tsx
frontend/src/app/login/page.tsx
frontend/src/app/register/page.tsx
frontend/src/app/dashboard/page.tsx
frontend/src/shared/config/env.ts
frontend/src/shared/api/http-client.ts
frontend/src/shared/api/api-error.ts
frontend/src/contexts/identity-access/domain/account.ts
frontend/src/contexts/identity-access/domain/account-status.ts
frontend/src/contexts/identity-access/domain/auth-session.ts
frontend/src/contexts/identity-access/domain/auth-error.ts
frontend/src/contexts/identity-access/application/ports/auth-gateway.ts
frontend/src/contexts/identity-access/application/ports/auth-session-store.ts
frontend/src/contexts/identity-access/application/use-cases/login.ts
frontend/src/contexts/identity-access/application/use-cases/register.ts
frontend/src/contexts/identity-access/application/use-cases/refresh-session.ts
frontend/src/contexts/identity-access/application/use-cases/logout.ts
frontend/src/contexts/identity-access/application/use-cases/bootstrap-session.ts
frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts
frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts
frontend/src/contexts/identity-access/infrastructure/state/auth-memory-store.ts
frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx
frontend/src/contexts/identity-access/presentation/ui/RegisterForm.tsx
frontend/src/contexts/identity-access/presentation/ui/LogoutButton.tsx
frontend/src/contexts/identity-access/presentation/guards/ProtectedRoute.tsx
frontend/src/contexts/identity-access/presentation/hooks/useAuth.ts
```

## Risks / trade-offs

- More files than a flat Next.js app.
- Access token is lost after page reload by design.
- Session bootstrap must call refresh before protected routes redirect.
- API client must prevent infinite refresh loops.
- Frontend and backend CORS/cookie settings must be tested together.
- Frontend must not duplicate backend authorization or workflow decisions.
- Error handling must use stable backend error codes, not random message strings.
- Contexts must not deep-import implementation details from each other.

## Acceptance criteria

- Frontend workspace exists under `frontend/`.
- Frontend implementation starts with `contexts/identity-access`.
- Access token is stored in memory only.
- Refresh token is never exposed to JavaScript.
- Auth requests use credentials.
- Protected routes support `loading`, `authenticated`, and `guest` states.
- API client retries an expired access token request once after successful refresh.
- API client does not infinite-loop on repeated `401` responses.
- Logout calls backend and clears local auth state.
- React components do not contain raw API calls.
- Application use cases depend on ports, not concrete infrastructure.
