# Frontend Checklist: Issue #53 integrate GET /api/auth/me

- [x] Confirm implementation branch/base is correct before code edits: expected `feature/53-integrate-frontend-auth-me-endpoint` or approved base with issue #50 backend `/api/auth/me`.
- [x] Extend `AuthGateway` with `getCurrentAccount(accessToken: string): Promise<Account>`.
- [x] Add explicit `CurrentAccountResponse` contract for `GET /api/auth/me`.
- [x] Implement `AuthHttpGateway.getCurrentAccount` using bearer `accessToken` and mapping to frontend `Account`.
- [x] Update bootstrap orchestration to refresh, call `/me`, save a full `AuthSession`, and avoid persisting an intermediate token-only session.
- [x] Handle refresh 401 and `/me` 401 during bootstrap by clearing memory state and returning unauthenticated state without retry loops.
- [x] Keep `useAuth`, `ProtectedRoute`, and app pages free of raw HTTP calls, backend URL construction, backend error parsing, and refresh algorithms.
- [x] Preserve memory-only access token handling and avoid localStorage, sessionStorage, IndexedDB, readable cookies, URL params, persisted stores, or `NEXT_PUBLIC_*` token exposure.
- [ ] Add focused automated tests if frontend test tooling is introduced or available; otherwise document the tooling gap and manual smoke coverage.
- [x] Run `cd frontend && npm run typecheck`.
- [ ] Run `cd frontend && npm run lint`.
- [ ] Run `cd frontend && npm run build`.
- [ ] Manually verify login, protected-route reload/bootstrap with `/me`, refresh failure redirect, `/me` 401 behavior, and logout.
