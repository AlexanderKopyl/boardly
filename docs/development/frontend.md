# Frontend Development

The Boardly frontend is a Next.js TypeScript application located under `frontend/`. It consumes the Symfony HTTP API backend and owns all product UI: routing, page composition, client-side state, and form rendering. The backend remains authoritative for permissions, workflow rules, and business state.

Related ADRs:

- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`

---

## Prerequisites

- Node.js and npm must be installed.
- npm is the selected package manager for this project. Do not use pnpm or yarn unless a future ADR changes this decision.
- All frontend commands run from the `frontend/` directory.

---

## First setup

```bash
cd frontend
npm install
cp .env.example .env.local
```

`.env.local` must not be committed to the repository. It is listed in `.gitignore`.

---

## Environment config

The only required frontend environment variable is:

```
NEXT_PUBLIC_API_URL=http://127.0.0.1:8080
```

Rules:

- Only public, non-sensitive frontend config uses the `NEXT_PUBLIC_*` prefix.
- Do not expose backend secrets through environment variables.
- Do not store access tokens or refresh tokens in environment variables.
- Tokens are never config values — they are runtime session state.

---

## Daily commands

```bash
cd frontend

# Start dev server
npm run dev

# Type check
npm run typecheck

# Lint
npm run lint

# Production build
npm run build
```

`npm run lint` runs `eslint .` directly. Do not use `next lint` — the project lint script is the canonical command.

---

## Source structure

```text
frontend/src/
  app/
  shared/
  contexts/
    identity-access/
      domain/
      application/
      infrastructure/
      presentation/
```

### app

`frontend/src/app/` owns Next.js routing, layouts, and page composition only.

Pages compose context presentation components. They must not contain raw API calls, backend URL construction, error normalization, token refresh logic, or business rules.

### shared

`frontend/src/shared/` contains cross-context utilities, the HTTP client, normalized API errors, and generic UI primitives.

Shared code must not depend on any product context. Shared UI components must stay generic.

### contexts/identity-access

`frontend/src/contexts/identity-access/` is the authentication context. It is split into four layers:

```text
domain/          — frontend-level types: Account, AccountStatus, AuthSession, AuthErrorCode
application/     — use cases and ports: login, register, refreshSession, logout, bootstrapSession
infrastructure/  — adapters: AuthHttpGateway, AuthMemoryStore
presentation/    — React components, hooks, guards: LoginForm, RegisterForm, ProtectedRoute, useAuth
```

Future contexts (`projects`, `issues`, `boards`, `workflow`, `notifications`) must not be created until a real implementation slice requires them.

---

## Layer rules

### Domain

The `domain` layer contains frontend-level models and pure TypeScript types.

Rules:

- no React imports
- no Next.js imports
- no HTTP client imports
- no browser storage access
- frontend domain models are not backend Doctrine entities and not backend aggregates
- frontend domain models must not encode authoritative backend business invariants

### Application

The `application` layer contains use cases and port interfaces.

Rules:

- use cases orchestrate browser-side flows
- use cases depend on ports/interfaces only
- no React imports
- no Next.js imports
- no raw `fetch` calls
- no `localStorage`, `sessionStorage`, cookies, or environment variables accessed directly
- no concrete infrastructure dependencies

### Infrastructure

The `infrastructure` layer contains adapters.

Rules:

- HTTP gateways translate backend API contracts into frontend domain/application models
- state adapters implement memory-only session storage where required
- infrastructure may depend on `shared/api` and `shared/config`
- infrastructure must hide transport details from presentation

### Presentation

The `presentation` layer contains React components, hooks, guards, and form UI.

Rules:

- presentation calls application use cases or context hooks
- presentation renders UI state and handles form state
- presentation does not call backend directly with raw `fetch`
- presentation does not own token refresh algorithms, retry logic, or API error normalization
- presentation does not make authoritative backend business decisions

---

## HTTP and API rules

All raw `fetch` calls are centralized in `frontend/src/shared/api/http-client.ts`. React components and context infrastructure must not call `fetch` directly outside of this module.

The HTTP client always sends:

```ts
fetch(url, {
  credentials: 'include',
  // ...
});
```

`credentials: 'include'` is required for all requests so the browser attaches the HttpOnly refresh cookie on auth-related calls.

Protected API requests pass the access token via the `Authorization` header:

```http
Authorization: Bearer <accessToken>
```

Backend error responses are normalized through `ApiError` (`frontend/src/shared/api/api-error.ts`). Presentation components must use `ApiError` properties, not raw error message strings, to drive UI behavior.

The HTTP client handles a single 401 retry. If `onUnauthorized` is provided and returns a new token, the failed request is retried once. If the retry also returns 401, the error is thrown and no further retry is attempted.

---

## Auth contracts

### Register

Request to `POST /api/auth/register`:

```json
{
  "email": "user@example.com",
  "plainPassword": "plain-password",
  "name": "User Name"
}
```

Response (201 Created):

```json
{
  "id": "account-id",
  "email": "user@example.com",
  "name": "User Name",
  "status": "pending_approval"
}
```

Registration behavior:

- creates an account with status `pending_approval`
- does not issue a JWT access token
- does not create a refresh token or session
- does not set a `refresh_token` cookie
- account cannot log in until a system admin approves it

### Login

Request to `POST /api/auth/login`:

```json
{
  "email": "user@example.com",
  "plainPassword": "plain-password"
}
```

Response (200 OK):

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

### Refresh

Request to `POST /api/auth/refresh` (no body required):

- the browser automatically sends the HttpOnly `refresh_token` cookie
- the request must include `credentials: 'include'`
- the request must include `X-CSRF-Intent: auth-refresh` header

Response (200 OK):

```json
{
  "accessToken": "jwt",
  "tokenType": "Bearer",
  "expiresIn": 900
}
```

### Logout

Request to `POST /api/auth/logout`:

- requires `credentials: 'include'`
- requires `X-CSRF-Intent: auth-refresh` header
- response is 204 No Content
- logout is idempotent

---

## Token rules

| Token | Storage | Who manages it |
|---|---|---|
| Access token (JWT) | JavaScript memory only | Frontend stores in `AuthMemoryStore` |
| Refresh token (opaque) | HttpOnly Secure cookie | Browser — set/cleared by backend |

Forbidden storage locations for tokens:

- `localStorage`
- `sessionStorage`
- `IndexedDB`
- JavaScript-readable cookies
- URL parameters
- persisted Zustand/Redux storage
- `NEXT_PUBLIC_*` environment variables

The frontend must never manually read, store, or attach the refresh token. The browser handles the cookie automatically when `credentials: 'include'` is used.

After a browser reload the access token is lost by design. The frontend must call `POST /api/auth/refresh` on bootstrap to restore the session from the HttpOnly cookie.

---

## Adding new frontend code

Checklist when adding a new feature or screen:

- An `app/` page composes presentation components; it does not build API requests
- `shared/` additions must remain context-free
- A new context application layer uses ports — no concrete infrastructure dependencies
- A new context infrastructure layer implements those ports
- Presentation components call use cases or hooks — no raw `fetch` in components
- Run `npm run typecheck` and `npm run lint` before opening a PR
- Run `npm run build` to verify there are no build errors before opening a PR

---

## Generated and ignored files

| Path | Notes |
|---|---|
| `frontend/node_modules/` | ignored, not committed |
| `frontend/.next/` | ignored, not committed |
| `frontend/*.tsbuildinfo` | ignored, not committed |
| `frontend/package-lock.json` | committed, represents the locked dependency tree |
