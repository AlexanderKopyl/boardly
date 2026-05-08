# Boardly Frontend

## Current frontend context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

The repository currently keeps the Symfony backend at the repository root. The frontend workspace starts under:

```text
frontend/
```

Accepted architecture decisions already define the frontend/backend direction:

- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`

The backend remains the source of truth for identity, permissions, workflow rules, project state, task state, and persistence.

## Architectural assessment

The frontend must not start from project/task screens.

The first frontend milestone is authentication integration because protected product UI depends on a stable session model.

Initial constraints:

- Next.js is the primary product UI direction.
- TypeScript is required.
- Symfony exposes the API.
- Access token is returned in JSON and stored in memory only.
- Refresh token is an opaque HttpOnly cookie managed by the browser.
- Refresh token must never be read by JavaScript.
- Refresh happens through `POST /api/auth/refresh`.
- Logout happens through `POST /api/auth/logout`.
- Auth requests must include browser credentials.

## Recommended direction

Start with a small vertical auth slice:

1. API client foundation.
2. Frontend environment config.
3. Auth API module.
4. Auth types.
5. Non-persisted auth store/provider.
6. Login page.
7. Register page.
8. Refresh-on-bootstrap.
9. Logout action.
10. Protected route guard.
11. Basic authenticated dashboard placeholder.

Do not implement Project, Issue, Board, Workflow, or Reporting UI before the authentication flow is stable.

## Target source structure

```text
frontend/
  src/
    app/
    shared/
      api/
        http-client.ts
        api-error.ts
      config/
        env.ts
      ui/
    features/
      auth/
        api/
          auth-api.ts
        model/
          auth-store.ts
          auth-types.ts
        lib/
          auth-guards.ts
        ui/
          LoginForm.tsx
          RegisterForm.tsx
```

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
frontend/src/app/layout.tsx
frontend/src/app/page.tsx
frontend/src/app/login/page.tsx
frontend/src/app/register/page.tsx
frontend/src/app/dashboard/page.tsx
frontend/src/shared/config/env.ts
frontend/src/shared/api/http-client.ts
frontend/src/shared/api/api-error.ts
frontend/src/features/auth/api/auth-api.ts
frontend/src/features/auth/model/auth-types.ts
frontend/src/features/auth/model/auth-store.ts
frontend/src/features/auth/lib/auth-guards.ts
frontend/src/features/auth/ui/LoginForm.tsx
frontend/src/features/auth/ui/RegisterForm.tsx
```

## Risks / trade-offs

- Access token is lost after page reload by design.
- Session bootstrap must call refresh before protected routes redirect.
- API client must prevent infinite refresh loops.
- Frontend and backend CORS/cookie settings must be tested together.
- Frontend must not duplicate backend authorization or workflow decisions.
- Error handling must use stable backend error codes, not random message strings.

## Acceptance criteria

- Frontend workspace exists under `frontend/`.
- Frontend implementation starts with auth infrastructure.
- Access token is stored in memory only.
- Refresh token is never exposed to JavaScript.
- Auth requests use credentials.
- Protected routes support `loading`, `authenticated`, and `guest` states.
- API client retries an expired access token request once after successful refresh.
- API client does not infinite-loop on repeated `401` responses.
- Logout calls backend and clears local auth state.
