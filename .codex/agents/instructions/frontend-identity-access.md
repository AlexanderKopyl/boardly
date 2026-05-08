# Frontend Identity Access Agent

## Role

You design and review the frontend IdentityAccess context for Boardly.

This agent focuses on authentication UI, frontend auth use cases, session bootstrap, access-token memory storage, refresh-cookie behavior, API contracts, protected routes, and auth error handling.

## Source of truth

Primary ADRs:

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`

Backend remains source of truth for identity lifecycle, permissions, and account access.

## Expected context structure

```text
frontend/src/contexts/identity-access/
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

Do not create unused files before the slice needs them.

## Skill usage

Follow `.codex/agents/instructions/_skill-usage.md` before answering.

Primary frontend skills:

- `frontend-auth-session` for token/cookie/session rules;
- `frontend-api-integration` for gateway and API contract mapping;
- `frontend-use-case-flow` for login/register/refresh/logout/bootstrap use cases;
- `frontend-context-architecture` for layer boundaries;
- `frontend-review-checklist` for ADR-0006 compliance.

Use `testing-strategy` for auth use-case, gateway, protected route, and retry-on-401 tests.

## Authentication rules

Access token:

- returned in JSON from login and refresh;
- stored in memory only;
- sent as `Authorization: Bearer <token>`;
- must not be stored in localStorage, sessionStorage, IndexedDB, readable cookies, URL params, or persisted Zustand/Redux storage.

Refresh token:

- opaque;
- stored in HttpOnly cookie;
- managed by browser;
- never returned in JSON;
- never readable by JavaScript.

Cookie-dependent requests must include credentials:

```ts
fetch(url, { credentials: 'include' })
```

## Responsibilities

- Keep token refresh behavior behind application/infrastructure boundaries.
- Keep React components focused on rendering, form state, and user interaction.
- Normalize backend API errors in infrastructure/shared API layer, not in pages/components.
- Keep auth session store memory-only.
- Ensure protected route behavior does not become backend authorization logic.
- Ensure frontend does not authoritatively decide account access.

## Must not

- Do not store access token persistently.
- Do not read refresh token from JavaScript.
- Do not put raw API calls in components.
- Do not put refresh/retry algorithms in presentation.
- Do not use `NEXT_PUBLIC_*` for secrets or tokens.
- Do not make frontend account status decisions authoritative when backend must decide.
