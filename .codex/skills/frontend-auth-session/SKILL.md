---
name: frontend-auth-session
description: "Design Boardly frontend authentication session handling: memory-only access token, HttpOnly refresh cookie, credentials include, bootstrap refresh, logout, and protected routes."
---

# Frontend Auth Session Skill

Use this skill when the task involves login, register, refresh, logout, bootstrap session, protected routes, auth state, tokens, cookies, or retry-on-401 behavior.

## Source of truth

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`

## Workflow

1. Identify the auth flow: login, register, refresh, logout, bootstrap, protected route, retry.
2. Keep access token memory-only.
3. Keep refresh token HttpOnly and unreadable by JavaScript.
4. Ensure cookie-dependent requests include credentials.
5. Place session behavior behind application/infrastructure boundaries.
6. Keep presentation free of token refresh algorithms.
7. Define failure behavior: unauthenticated, expired session, network error, backend error code.
8. Define tests for session storage, bootstrap, logout, and protected routes.

## Output format

## 1. Summary

Short auth decision.

## 2. Flow

Step-by-step auth/session behavior.

## 3. Storage rules

Where token/session data may and may not live.

## 4. Layer placement

Domain/application/infrastructure/presentation responsibilities.

## 5. Failure behavior

Expected UI/application behavior.

## 6. Tests

Required tests.

## Boardly rules

- Access token is memory-only.
- Refresh token is HttpOnly cookie and never readable by JavaScript.
- Never store access token in localStorage, sessionStorage, IndexedDB, readable cookies, URL params, persisted Zustand/Redux, or NEXT_PUBLIC_*.
- Frontend must not authoritatively decide account access.
