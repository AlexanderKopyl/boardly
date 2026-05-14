# Analysis: Issue #53 integrate GET /api/auth/me into frontend IdentityAccess

Branch observed: `feature/50-add-get-apiauthme-endpoint-for-current-authenticated-account`  
Requested branch: `feature/53-integrate-frontend-auth-me-endpoint`  
Mode: analysis only, no implementation

## 1. Skills Used

- `repo-onboarding`
- `frontend-task-analysis`
- `frontend-context-architecture`
- `frontend-api-integration`
- `frontend-auth-session`
- `testing-strategy`

Guidance loaded:

- `AGENTS.md`
- `.codex/config.toml`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/development/backend/boardly-symfony-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

MemPalace was not used. Current repository files and task text were sufficient for this analysis.

## 2. Relevant Files Discovered

Frontend IdentityAccess:

- `frontend/src/contexts/identity-access/domain/account.ts`
- `frontend/src/contexts/identity-access/domain/account-status.ts`
- `frontend/src/contexts/identity-access/domain/auth-session.ts`
- `frontend/src/contexts/identity-access/domain/auth-error.ts`
- `frontend/src/contexts/identity-access/application/ports/auth-gateway.ts`
- `frontend/src/contexts/identity-access/application/ports/auth-session-store.ts`
- `frontend/src/contexts/identity-access/application/use-cases/login.ts`
- `frontend/src/contexts/identity-access/application/use-cases/refresh-session.ts`
- `frontend/src/contexts/identity-access/application/use-cases/bootstrap-session.ts`
- `frontend/src/contexts/identity-access/application/use-cases/logout.ts`
- `frontend/src/contexts/identity-access/application/use-cases/register.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts`
- `frontend/src/contexts/identity-access/infrastructure/state/auth-memory-store.ts`
- `frontend/src/contexts/identity-access/presentation/hooks/useAuth.tsx`
- `frontend/src/contexts/identity-access/presentation/guards/ProtectedRoute.tsx`

Shared/frontend tooling:

- `frontend/src/shared/api/http-client.ts`
- `frontend/src/shared/api/api-error.ts`
- `frontend/src/shared/config/env.ts`
- `frontend/src/app/providers.tsx`
- `frontend/package.json`

Backend contract evidence:

- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandler.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountResult.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/CurrentAccountResponse.php`
- `src/Boardly/IdentityAccess/Infrastructure/Security/BearerTokenAuthenticator.php`
- `src/Boardly/IdentityAccess/Infrastructure/Security/AuthenticationFailureResponseFactory.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginController.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php`

Task-state context:

- `docs/tasks/issues-49/analysis.md`
- `docs/tasks/issues-49/frontend-implementation.md`
- `docs/tasks/issues-50/analysis.md`

## 3. Current Auth Flow Summary

The frontend IdentityAccess context already follows the ADR-0006 layer structure:

| Layer | Current responsibility | Relevant files |
| --- | --- | --- |
| Domain | Frontend account/session/error types | `account.ts`, `account-status.ts`, `auth-session.ts`, `auth-error.ts` |
| Application | Auth ports and use cases | `auth-gateway.ts`, `login.ts`, `refresh-session.ts`, `bootstrap-session.ts`, `logout.ts`, `register.ts` |
| Infrastructure | HTTP adapter and memory-only session storage | `auth-http-gateway.ts`, `auth-api-contracts.ts`, `auth-memory-store.ts` |
| Presentation | React provider/hook and protected route composition | `useAuth.tsx`, `ProtectedRoute.tsx` |
| Shared | Generic HTTP mechanics and env config | `shared/api/http-client.ts`, `shared/config/env.ts` |

Current login flow:

```text
LoginForm
-> useAuth().login()
-> loginUseCase()
-> AuthGateway.login()
-> POST /api/auth/login
-> save AuthSession { accessToken, expiresAt, account }
```

`POST /api/auth/login` currently returns `accessToken`, `tokenType`, `expiresIn`, and an embedded safe `account`. The frontend maps that account into the domain `Account` model.

Current refresh/bootstrap flow:

```text
ProtectedRoute mounts
-> useAuth().bootstrap()
-> bootstrapSessionUseCase()
-> refreshSessionUseCase()
-> AuthGateway.refreshSession()
-> POST /api/auth/refresh with credentials and X-CSRF-Intent
-> save AuthSession { accessToken, expiresAt, account: null }
```

After issue #49, bootstrap no longer depends on `/api/auth/me`, so a browser reload can produce an authenticated token-only session with `account: null`. This lets a protected route render but does not restore current account identity for UI surfaces that need name, email, or status.

Current logout flow:

```text
useAuth().logout()
-> logoutUseCase()
-> AuthGateway.logout()
-> POST /api/auth/logout with credentials and X-CSRF-Intent
-> always clear memory store
```

Current token storage:

- Access token lives only in the module-level memory store.
- Refresh token is never read by frontend JavaScript.
- `httpRequest` always uses `credentials: 'include'`, which is required for refresh/logout cookie flows.
- `httpRequest` adds `Authorization: Bearer <accessToken>` only when an access token is supplied.

## 4. Contract Gaps

GitHub issue #53 gap:

- The exact GitHub issue body could not be read with available tools. The analysis uses the user's task text as the issue #53 contract and local issue #50/backend implementation as endpoint evidence.

Repository state gap:

- The checked-out branch is issue #50, not issue #53. Planning should confirm the intended base before implementation.

Frontend API gaps:

- `AuthGateway` has no `getCurrentAccount` or `getCurrentAccount(accessToken)` operation.
- `auth-api-contracts.ts` has `AccountResponse`, but no explicit `CurrentAccountResponse` alias/interface for `/api/auth/me`.
- `AuthHttpGateway` has no `GET /api/auth/me` method.
- `bootstrapSessionUseCase` cannot restore account data after refresh.
- `refreshSessionUseCase` currently commits a token-only session immediately. If `/me` is added to bootstrap, the application flow should avoid briefly publishing a durable authenticated session with `account: null` unless the UI explicitly supports that intermediate state.
- `AuthErrorCode` has `invalid_refresh_token` but no clear code for a bearer-auth `/me` failure such as `unauthenticated`, `current_account_unavailable`, or `invalid_access_token`.

Backend contract facts:

- `GET /api/auth/me` returns a flat JSON object:

```json
{
  "id": "account-id",
  "email": "user@example.com",
  "name": "User Name",
  "status": "active"
}
```

- It requires `Authorization: Bearer <accessToken>`.
- It does not require a request body.
- It does not require the `X-CSRF-Intent` header because it is not a cookie-mutating refresh/logout operation.
- Generic 401 response shape is:

```json
{
  "error": {
    "code": "unauthorized",
    "message": "Authentication required."
  }
}
```

## 5. Architecture Impact

### Context Boundary

`GET /api/auth/me` belongs inside `frontend/src/contexts/identity-access`. It is an IdentityAccess session/account bootstrap operation, not shared API behavior and not an app-route concern.

No new frontend context is justified.

### Recommended Layer Placement

| Layer | Recommendation |
| --- | --- |
| Domain | Reuse `Account` and `AccountStatus`; do not create backend aggregate-shaped models. |
| Application port | Extend `AuthGateway` with a current-account operation, preferably `getCurrentAccount(accessToken: string): Promise<Account>`. |
| Application use case | Add a `getCurrentAccountUseCase` or compose it inside a bootstrap-specific use case. Use cases should orchestrate session restoration, not React hooks. |
| Infrastructure contract | Add `CurrentAccountResponse` as the explicit `/api/auth/me` response contract, even if it has the same fields as `AccountResponse`. |
| Infrastructure gateway | Implement `GET /api/auth/me` in `AuthHttpGateway`, passing `accessToken` to shared `httpRequest`. Map the response to frontend `Account`. |
| Shared API | Reuse `httpRequest` bearer support. Do not make shared API know about IdentityAccess account semantics. |
| Presentation | Keep `useAuth` and `ProtectedRoute` free of raw HTTP calls and token-refresh algorithms. They should call application use cases only. |

### Suggested Flow Decision

Preferred bootstrap flow:

```text
bootstrapSessionUseCase
-> AuthGateway.refreshSession()
-> AuthGateway.getCurrentAccount(newAccessToken)
-> save AuthSession { accessToken, expiresAt, account }
```

This restores the current account after browser reload while preserving memory-only access-token storage.

Login should continue using the embedded login `account` unless issue #53 explicitly requires validating it through `/me`. Calling `/me` after every login is redundant with the current backend login contract and adds avoidable latency. The main gap is refresh/bootstrap, where refresh returns only a token.

Protected-route behavior should remain presentation-only:

```text
ProtectedRoute
-> bootstrap()
-> if no session after bootstrap, redirect to /login
```

The route guard should not decide whether `/me` should be retried, refreshed, or mapped.

### Authorization Header

`GET /api/auth/me` should be called through `httpRequest('/api/auth/me', { accessToken })`.

The shared client already turns that into:

```text
Authorization: Bearer <accessToken>
credentials: include
Accept: application/json
```

Including credentials is acceptable because the shared client does it globally, but `/me` authentication must depend on the bearer token, not on reading the refresh cookie.

### 401 Handling

For bootstrap:

- If refresh returns 401, clear memory store and return `null`.
- If refresh succeeds but `/api/auth/me` returns 401, clear memory store and return `null`.
- Do not loop by calling refresh again after a `/me` 401 during bootstrap, because the access token was just obtained from refresh.

For a standalone current-account use case:

- Map `/me` 401 to an auth-domain error distinct from `invalid_refresh_token`, or document why the existing code is intentionally reused.
- A clearer model would add something like `unauthenticated` or `invalid_access_token` to `AuthErrorCode`.
- Presentation should consume the resulting `null` session or auth error; it should not parse backend `ApiError` directly.

For future protected API calls:

- `httpRequest` already supports an `onUnauthorized` retry hook.
- Issue #53 does not need to solve general retry-on-401 for all contexts, but the `/me` implementation should not block that future direction.

## 6. Testing/Tooling Facts

`frontend/package.json` currently has:

```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "eslint .",
    "typecheck": "tsc --noEmit"
  }
}
```

No frontend test runner is currently configured:

- No `test` script.
- No Vitest/Jest/Playwright dependency in `frontend/package.json`.
- No frontend `*.test.ts`, `*.test.tsx`, `*.spec.ts`, or `*.spec.tsx` files were found by narrow search.

Minimum quality gate for issue #53 if test tooling remains unchanged:

- `npm run typecheck`
- `npm run lint`
- Manual or browser verification of login, protected-route reload/bootstrap, and logout after `/me` integration.

Recommended test strategy once tooling exists:

| Layer | What to test | Why |
| --- | --- | --- |
| Application | Bootstrap refresh success followed by `/me` success saves full session | Protects reload behavior |
| Application | Refresh 401 clears session and returns unauthenticated state | Protects expired/revoked refresh sessions |
| Application | `/me` 401 after refresh clears session and returns unauthenticated state | Protects disabled/deleted/current-account failures |
| Infrastructure | `AuthHttpGateway.getCurrentAccount` sends bearer access token | Protects ADR-0005 auth contract |
| Infrastructure | `/me` response maps to `Account` exactly | Prevents leaking backend contract details |
| Infrastructure | `/me` 401 maps to normalized auth error | Keeps presentation free of backend error parsing |
| Presentation | `ProtectedRoute` redirects only after bootstrap completes with no session | Prevents loading/redirect flicker regressions |

Permission/workflow/search/audit/async tests are not applicable to issue #53. This is an IdentityAccess session bootstrap integration and must not add frontend authority for permissions, workflow transitions, project state, or audit decisions.

## 7. Risks/Blockers

- Branch mismatch: local branch is issue #50, while the task names issue #53. Implementation planning should start from the intended branch/base.
- Issue contract uncertainty: GitHub issue #53 body was not accessible through available tools.
- If `/me` is integrated only at gateway level and not composed into bootstrap, the visible user-facing gap remains: reload sessions still have `account: null`.
- If `/me` 401 is mapped to `invalid_refresh_token`, the code will work for bootstrap but the domain language will be misleading because `/me` uses bearer auth, not the refresh token.
- If presentation calls `/me` directly from `useAuth` or `ProtectedRoute`, it violates ADR-0006 and duplicates token/session orchestration in React.
- If access tokens are placed in localStorage, sessionStorage, readable cookies, URL params, or persisted state while wiring `/me`, it violates ADR-0005.
- If frontend treats account status from `/me` as permission authority, it violates the backend-source-of-truth rule. The frontend may render current identity; backend still owns access decisions.
- No frontend unit/integration test tooling exists yet. The first implementation may need either test tooling setup or documented manual verification.

## 8. Recommended Next Step: Planning

Proceed to planning for issue #53 after confirming the working branch/base.

Planning should define a narrow implementation path:

1. Extend the IdentityAccess auth gateway port with a current-account operation.
2. Add the `/api/auth/me` response contract and HTTP gateway mapping.
3. Add an application use case or bootstrap composition that refreshes first, then loads current account with the fresh bearer token, then saves one full `AuthSession`.
4. Define `/me` 401 behavior as unauthenticated session state and avoid refresh loops during bootstrap.
5. Keep `useAuth` and `ProtectedRoute` as presentation orchestration only.
6. Decide whether to add frontend test tooling now or record `lint`, `typecheck`, and manual browser verification as the quality gate.
