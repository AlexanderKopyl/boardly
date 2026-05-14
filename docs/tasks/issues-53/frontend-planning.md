# Frontend Planning: Issue #53 integrate GET /api/auth/me into IdentityAccess

## Goal

Integrate `GET /api/auth/me` into the frontend IdentityAccess session flow so a browser reload can restore both a memory-only access token and the current account profile after refresh.

## Scope

- Extend the IdentityAccess auth gateway port with a current-account operation.
- Add the `/api/auth/me` HTTP contract and gateway mapping.
- Compose refresh plus current-account loading in the bootstrap flow.
- Keep access tokens memory-only and refresh cookies HttpOnly/unreadable.
- Keep presentation free of raw HTTP calls, backend error parsing, and refresh algorithms.
- Preserve the current login/register/logout UI shape unless a direct integration point needs a small adjustment.

## Non-goals

- Do not add project, issue, board, workflow, permission, or admin UI.
- Do not add persistent token storage.
- Do not make frontend account status authoritative for access decisions.
- Do not redesign the auth UI.
- Do not solve general retry-on-401 for all future API calls in this issue.
- Do not introduce frontend test tooling unless implementation explicitly decides that setup is in scope.

## Guidance loaded

- [x] AGENTS.md
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/development/backend/boardly-symfony-developer-rules.md`
- [x] ADR-0004
- [x] ADR-0005
- [x] ADR-0006
- [x] ADR-0007
- [x] Existing task artifacts: `docs/tasks/issues-53/onboarding.md`, `docs/tasks/issues-53/analysis.md`

MemPalace was not used. Existing repository artifacts were sufficient and current code is the source of truth.

## Current Repository Facts

- Observed branch: `feature/50-add-get-apiauthme-endpoint-for-current-authenticated-account`.
- Requested branch: `feature/53-integrate-frontend-auth-me-endpoint`.
- Existing analysis records this branch mismatch. Treat it as an implementation risk and verify the intended branch/base before editing code.
- `frontend/package.json` has `dev`, `build`, `start`, `lint`, and `typecheck`; it has no `test` script and no frontend test runner dependencies.
- `AuthGateway` currently exposes `login`, `register`, `refreshSession`, and `logout`; it does not expose `/api/auth/me`.
- `refreshSessionUseCase` saves `AuthSession` with `account: null`.
- `bootstrapSessionUseCase` delegates to refresh and returns a token-only session when refresh succeeds.
- `useAuth` calls `bootstrapSessionUseCase` and stores the returned session; it does not perform raw HTTP.
- Shared `httpRequest` already supports `Authorization: Bearer <accessToken>`, `credentials: 'include'`, normalized `ApiError`, and optional one-time `onUnauthorized`.

## Frontend Context Ownership

`GET /api/auth/me` belongs to `frontend/src/contexts/identity-access`.

It is an IdentityAccess account/session restoration operation. It is not shared API behavior, not route-level behavior, and not a new frontend context. The backend remains authoritative for account lifecycle and access decisions; the frontend current-account model is only a client-side session/profile representation.

## Layer/File Placement

| Layer | Responsibility | Planned files |
| --- | --- | --- |
| Domain | Reuse frontend `Account`, `AccountStatus`, and `AuthSession`; optionally add an auth error code for bearer-auth current-account failure. | `frontend/src/contexts/identity-access/domain/account.ts`, `account-status.ts`, `auth-session.ts`, `auth-error.ts` |
| Application ports | Add a current-account gateway operation that requires an access token. | `frontend/src/contexts/identity-access/application/ports/auth-gateway.ts` |
| Application use cases | Add a current-account use case if useful, and update bootstrap to refresh, load `/me`, then save one full session. | `frontend/src/contexts/identity-access/application/use-cases/bootstrap-session.ts`, possible `get-current-account.ts`, possible `refresh-session.ts` adjustment |
| Infrastructure HTTP contracts | Add an explicit `/api/auth/me` response contract even if it currently matches `AccountResponse`. | `frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts` |
| Infrastructure HTTP gateway | Implement `GET /api/auth/me` through `httpRequest`, passing the bearer token and mapping transport data to `Account`. | `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts` |
| Infrastructure state | Keep existing memory-only session store. No persistent storage changes. | `frontend/src/contexts/identity-access/infrastructure/state/auth-memory-store.ts` |
| Presentation | Keep `useAuth` and `ProtectedRoute` as application-use-case consumers only; no raw `/me` call in React. | `frontend/src/contexts/identity-access/presentation/hooks/useAuth.tsx`, `presentation/guards/ProtectedRoute.tsx` |
| Shared API | Reuse existing bearer/header/error behavior; do not add IdentityAccess semantics to shared code. | `frontend/src/shared/api/http-client.ts`, `frontend/src/shared/api/api-error.ts` |
| App routes/UI | Minimal or no changes expected. Pages remain composition-only. | `frontend/src/app/*` only if implementation exposes current account in existing UI |

## API/Auth/Session Impact

### Backend operation

`GET /api/auth/me`:

- requires `Authorization: Bearer <accessToken>`;
- returns current account identity/profile data;
- uses no request body;
- does not require `X-CSRF-Intent`;
- returns 401 for unauthenticated or invalid bearer-token cases.

Expected frontend response contract:

```ts
export interface CurrentAccountResponse {
  id: string
  email: string
  name: string
  status: AccountStatus
}
```

### Port decision

Extend `AuthGateway` with:

```ts
getCurrentAccount(accessToken: string): Promise<Account>
```

The access token parameter keeps the use case explicit and avoids letting the gateway read session state directly.

### Gateway decision

`AuthHttpGateway.getCurrentAccount` should call:

```ts
httpRequest<CurrentAccountResponse>('/api/auth/me', { accessToken })
```

The gateway maps the API contract to the frontend `Account` model. Shared `httpRequest` owns HTTP mechanics only.

### Bootstrap decision

Preferred bootstrap flow:

```text
bootstrapSessionUseCase
-> gateway.refreshSession()
-> gateway.getCurrentAccount(refreshResult.accessToken)
-> store.save({ accessToken, expiresAt, account })
-> return full AuthSession
```

This avoids a durable authenticated session with `account: null` after reload. If implementation keeps `refreshSessionUseCase` as a token-only use case for future standalone use, `bootstrapSessionUseCase` should not call it directly unless it can avoid saving the intermediate token-only session.

### Login/refresh interaction

- Login can continue using the embedded `account` from `POST /api/auth/login`; calling `/me` after every login is redundant unless issue #53 explicitly requires it.
- Refresh can remain a token-only gateway operation.
- Bootstrap composes refresh plus `/me` because refresh alone cannot restore account data.
- Logout continues to clear memory state after calling the backend.

### 401 handling

- Refresh 401 during bootstrap: clear store and return `null`.
- `/me` 401 after a successful refresh: clear store and return `null`.
- Do not attempt a refresh loop after `/me` 401 during bootstrap because the token was just issued.
- Add or document a bearer-auth error code such as `unauthenticated` or `invalid_access_token`; do not misleadingly treat `/me` 401 as `invalid_refresh_token` outside the bootstrap unauthenticated outcome.

### Storage rules

- Access token remains only in `AuthMemoryStore`/React state.
- Do not write tokens to `localStorage`, `sessionStorage`, IndexedDB, readable cookies, URL params, persisted stores, or `NEXT_PUBLIC_*`.
- Refresh token remains an HttpOnly cookie and is never read by JavaScript.

## UI/Style Impact

Minimal UI impact is expected.

- Existing pages, forms, and `ProtectedRoute` should keep their current user-visible behavior.
- Existing loading state can cover refresh plus `/me`.
- No new shared UI primitive is planned.
- ADR-0007 still applies if implementation chooses to display current account data in an existing UI surface, but this issue should not redesign screens.

## Subagents to Use

- `frontend-identity-access` for auth/session and IdentityAccess boundary decisions.
- `frontend-context-architect` if implementation touches import boundaries or adds a use-case file.
- `frontend-reviewer` for ADR-0006/auth safety review after implementation.
- `testing-security-reviewer` if frontend tests or security-sensitive error handling are added.

## Skills to Use

- `frontend-task-implementation`
- `frontend-context-architecture`
- `frontend-api-integration`
- `frontend-auth-session`
- `testing-strategy`
- `verification-evidence` before closing the task

## Planning Decisions

1. Keep `/api/auth/me` inside the IdentityAccess context.
2. Add a current-account operation to `AuthGateway`; do not call raw HTTP from `useAuth`, `ProtectedRoute`, or pages.
3. Add an explicit `CurrentAccountResponse` contract and map it to `Account` in the HTTP gateway.
4. Bootstrap should refresh first, then call `/me` with the fresh bearer token, then save one full session.
5. Login should keep using the account embedded in the login response unless the inaccessible GitHub issue body later says otherwise.
6. Treat `/me` 401 after refresh as an unauthenticated bootstrap result, clear memory state, and avoid refresh loops.
7. Preserve memory-only access-token handling and HttpOnly refresh-cookie assumptions.
8. Keep UI changes minimal; existing loading/protected-route behavior is enough for this slice.

## Implementation Checklist

- [ ] Confirm branch/base before code edits: expected `feature/53-integrate-frontend-auth-me-endpoint` or an approved base containing issue #50 backend `/api/auth/me`.
- [ ] Extend the IdentityAccess application port with `getCurrentAccount(accessToken)`.
- [ ] Add the `/api/auth/me` transport contract and gateway mapping to frontend `Account`.
- [ ] Update bootstrap orchestration to refresh, fetch current account, save a full `AuthSession`, and clear memory on refresh or `/me` 401.
- [ ] Review `useAuth`/`ProtectedRoute` integration so presentation remains a use-case consumer and keeps the existing loading/redirect behavior.
- [ ] Add focused tests if frontend test tooling exists or is introduced; otherwise document the missing test runner and perform manual auth smoke verification.
- [ ] Run frontend verification commands and record results in the implementation/verification artifact.

## Validation Checklist

- [ ] `cd frontend && npm run typecheck`
- [ ] `cd frontend && npm run lint`
- [ ] `cd frontend && npm run build`
- [ ] Manual smoke: login succeeds and stores memory-only session with account.
- [ ] Manual smoke: reload/protected route bootstrap refreshes, calls `/me`, and restores account.
- [ ] Manual smoke: expired/invalid refresh redirects to login after bootstrap.
- [ ] Manual smoke: `/me` 401 after refresh clears session and does not loop.
- [ ] Manual smoke: logout clears session and does not leave a usable access token in memory.

## Test Strategy

| Layer | What to test | Why |
| --- | --- | --- |
| Application | Bootstrap refresh success followed by `/me` success saves full session with account. | Protects reload session restoration. |
| Application | Refresh 401 clears memory state and returns unauthenticated bootstrap state. | Protects expired/revoked refresh sessions. |
| Application | `/me` 401 after successful refresh clears memory state and returns unauthenticated bootstrap state. | Protects disabled/deleted/current-account failures without loops. |
| Infrastructure | `AuthHttpGateway.getCurrentAccount` sends the bearer access token. | Protects ADR-0005 bearer contract. |
| Infrastructure | `/me` response maps exactly to frontend `Account`. | Prevents backend contract leakage into presentation. |
| Infrastructure | `/me` 401 becomes a normalized auth-domain failure, not a UI-parsed `ApiError`. | Keeps presentation free of transport error parsing. |
| Presentation | `ProtectedRoute` waits for bootstrap before redirecting. | Prevents loading/redirect regressions. |

Permission, workflow, async, search/projection, and audit tests are not applicable to this frontend auth-session slice.

## Risks

- Branch mismatch: current branch is issue #50, but the task targets issue #53.
- GitHub issue #53 body was not available in prior analysis, so the user's task text and local backend evidence define the planning contract.
- If implementation only adds a gateway method and does not compose it into bootstrap, reload sessions will still have `account: null`.
- If `/me` 401 is represented as `invalid_refresh_token`, domain language becomes misleading.
- If React presentation owns `/me` calls or retry logic, ADR-0006 is violated.
- If any token is persisted outside memory, ADR-0005 is violated.
- Current frontend tooling lacks a test runner, so automated coverage may be limited to typecheck/lint/build unless tooling is added.

## Open Questions

- Does the actual GitHub issue #53 require calling `/me` after login too, or only using it for bootstrap/current-account restoration?
- Should this issue introduce frontend unit test tooling, or should the first implementation rely on current quality gates plus manual smoke verification?
- Should `refreshSessionUseCase` remain token-only for future standalone use, or should it be split so bootstrap can avoid saving an intermediate `account: null` session?

## First Recommended Implementation Slice

Start with the smallest non-UI vertical slice:

1. Confirm branch/base.
2. Add `AuthGateway.getCurrentAccount(accessToken)`.
3. Add `CurrentAccountResponse` and `AuthHttpGateway.getCurrentAccount`.
4. Typecheck this contract-level change before modifying bootstrap.

This creates the API integration boundary first, keeps React untouched, and gives the bootstrap change a concrete port to call.

## Next Artifact

Expected next artifact: `docs/tasks/issues-53/frontend-implementation.md`
