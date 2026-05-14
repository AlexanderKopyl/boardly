# Onboarding: Issue #53 integrate GET /api/auth/me into frontend IdentityAccess

## Task contract

Analyze how `GET /api/auth/me` should fit into the current frontend IdentityAccess architecture.

Constraints:

- Analysis only.
- Do not implement code.
- Do not create an implementation checklist.
- Use narrow retrieval.
- Save resumable task state under `docs/tasks/issues-53`.

Done criteria:

- Current auth flow is summarized.
- `/api/auth/me` frontend placement is analyzed across domain, application, infrastructure, presentation, and shared API boundaries.
- Auth/session, API integration, test tooling, and risks are recorded.
- Next step is planning.

## Durable guidance loaded

- `AGENTS.md`
- `.codex/config.toml`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/development/backend/boardly-symfony-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- Requested skills: `repo-onboarding`, `frontend-task-analysis`, `frontend-context-architecture`, `frontend-api-integration`, `frontend-auth-session`, `testing-strategy`

## Candidate files

| File | Reason | Confidence |
| --- | --- | --- |
| `frontend/src/contexts/identity-access/domain/account.ts` | Current frontend account model for `/me` mapping | High |
| `frontend/src/contexts/identity-access/domain/auth-session.ts` | Session currently allows `account: null` after refresh/bootstrap | High |
| `frontend/src/contexts/identity-access/application/ports/auth-gateway.ts` | Port currently lacks `getCurrentAccount` | High |
| `frontend/src/contexts/identity-access/application/use-cases/refresh-session.ts` | Refresh currently stores token-only session | High |
| `frontend/src/contexts/identity-access/application/use-cases/bootstrap-session.ts` | Browser reload bootstrap should likely call refresh then `/me` | High |
| `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts` | Concrete API adapter and error mapping | High |
| `frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts` | Backend DTO shapes for auth endpoints | High |
| `frontend/src/shared/api/http-client.ts` | Shared bearer header, credentials, and 401 retry mechanics | High |
| `frontend/src/contexts/identity-access/presentation/hooks/useAuth.tsx` | Provider/hook state flow after issue #49 | High |
| `frontend/src/contexts/identity-access/presentation/guards/ProtectedRoute.tsx` | Bootstrap trigger and unauthenticated redirect behavior | High |
| `frontend/package.json` | Frontend scripts and test tooling facts | High |
| `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php` | Local backend contract for `GET /api/auth/me` | High |
| `docs/tasks/issues-50/analysis.md` | Prior backend analysis for `/api/auth/me` intent | Medium |

## Files read

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
- `frontend/src/shared/api/http-client.ts`
- `frontend/src/shared/api/api-error.ts`
- `frontend/src/shared/config/env.ts`
- `frontend/src/app/providers.tsx`
- `frontend/package.json`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/GetCurrentAccountController.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountHandler.php`
- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountResult.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/OpenApi/Schema/CurrentAccountResponse.php`
- `src/Boardly/IdentityAccess/Infrastructure/Security/BearerTokenAuthenticator.php`
- `src/Boardly/IdentityAccess/Infrastructure/Security/AuthenticationFailureResponseFactory.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginController.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php`

## Facts learned

- Local branch is `feature/50-add-get-apiauthme-endpoint-for-current-authenticated-account`, not requested `feature/53-integrate-frontend-auth-me-endpoint`.
- `docs/tasks/issues-53` did not exist before this analysis.
- GitHub issue #53 could not be read through exposed tools. Generic web open returned no issue content, and no direct issue-read MCP tool is exposed. The analysis therefore uses the user's task text as the issue #53 contract plus local issue #50/backend evidence.
- Local backend code includes `GET /api/auth/me`.
- `/api/auth/me` returns a flat JSON account shape: `id`, `email`, `name`, `status`.
- `/api/auth/me` is bearer-auth protected and documents 401 for missing, invalid, expired, revoked, missing-account, or non-active bearer tokens.
- Frontend IdentityAccess already has domain/application/infrastructure/presentation layers per ADR-0006.
- `AuthGateway` currently supports `login`, `register`, `refreshSession`, and `logout`; it does not expose a current-account operation.
- `loginUseCase` stores a full session with account data from the login response.
- `refreshSessionUseCase` stores a token-only session with `account: null`.
- `bootstrapSessionUseCase` currently calls refresh only and returns `null` only for `AuthError('invalid_refresh_token')`.
- Shared `httpRequest` always sends `credentials: 'include'`, can attach `Authorization: Bearer <accessToken>`, normalizes backend error envelopes into `ApiError`, and supports optional one-time 401 retry through `onUnauthorized`.
- No frontend test runner is configured in `frontend/package.json`; only `lint` and `typecheck` scripts exist.

## Still unknown

- The exact body of GitHub issue #53 is unavailable in this environment.
- Whether issue #53 expects replacing login embedded account usage with `/me`, or only using `/me` for refresh/bootstrap, is not confirmed from the issue body.
- Whether the current local branch contains all issue #50 backend changes intended to be merged before issue #53 is not confirmed because the checked-out branch differs from the requested branch.

## Recommended next skill

Use frontend task planning next for issue #53, after confirming the repository is on the intended `feature/53-integrate-frontend-auth-me-endpoint` branch or that issue #50 backend changes are available as the base.
