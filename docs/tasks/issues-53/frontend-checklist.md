# Frontend Checklist: Issue #53 integrate GET /api/auth/me

- [x] Confirm implementation branch/base is correct before code edits: expected `feature/53-integrate-frontend-auth-me-endpoint` or approved base with issue #50 backend `/api/auth/me`.
- [x] Extend `AuthGateway` with `getCurrentAccount(accessToken: string): Promise<Account>`.
- [x] Add explicit `CurrentAccountResponse` contract for `GET /api/auth/me`.
- [x] Implement `AuthHttpGateway.getCurrentAccount` using bearer `accessToken` and mapping to frontend `Account`.
- [x] Update bootstrap orchestration to refresh, call `/me`, save a full `AuthSession`, and avoid persisting an intermediate token-only session.
- [x] Handle refresh 401 and `/me` 401 during bootstrap by clearing memory state and returning unauthenticated state without retry loops.
- [x] Keep `useAuth`, `ProtectedRoute`, and app pages free of raw HTTP calls, backend URL construction, backend error parsing, and refresh algorithms.
- [x] Preserve memory-only access token handling and avoid localStorage, sessionStorage, IndexedDB, readable cookies, URL params, persisted stores, or `NEXT_PUBLIC_*` token exposure.
- [x] Add focused automated tests if frontend test tooling is introduced or available; otherwise document the tooling gap and manual smoke coverage.
- [x] Run `cd frontend && npm run typecheck`.
- [x] Run `cd frontend && npm run lint`.
- [x] Run `cd frontend && npm run build`.
- [x] Wire the dashboard sidebar account block to authenticated `session.account` data with a minimal loading/token-only fallback.
- [x] Fix sidebar account block overflow so long authenticated names/emails stay inside the dark navy sidebar with ellipsis truncation and a fixed-size avatar.
- [x] Manually verify login, protected-route reload/bootstrap with `/me`, refresh failure redirect, `/me` 401 behavior, and logout.

## Dashboard Shell Layout

- [x] Rework the `/app/dashboard` sidebar to follow the prototype structure: Boardly brand, workspace card, `MAIN` nav, `WORK` nav, then footer account/logout.
- [x] Make the sidebar a full-height vertical flex layout with a scrollable primary area and a footer pinned to the bottom while preserving real account data and truncation.
- [x] Verify the dashboard shell with frontend typecheck, lint, build, and a browser smoke check on `/app/dashboard`.
