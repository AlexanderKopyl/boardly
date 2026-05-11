# Issue 49 Frontend Implementation

## Pass 1: Auth Contracts First

Date: 2026-05-11

## Scope

This pass completed the required pre-implementation confirmations and the Auth Contracts First checklist section only.

Backend code was inspected for current auth contracts, but no backend code was modified.

## Skills and Subagents Used

- Skills: `frontend-auth-session`, `frontend-api-integration`, `frontend-context-architecture`.
- Requested workflow: `frontend-task-implementation`; no matching available skill file was present, so the repository task artifacts and frontend rules were used directly.
- Subagents: `frontend_auth_explorer` and `frontend_auth_code_explorer` were started for frontend auth discovery. They did not return useful code findings before the implementation completed, so the implementation relied on direct repository inspection.

## Relevant Memory Found

- ADR-0005 governs short-lived JWT access tokens, memory-only frontend access-token storage, opaque HttpOnly refresh cookies, and refresh/logout CSRF intent.
- ADR-0006 governs the frontend context-based hexagonal structure: `app/` for route composition, `shared/` for generic infrastructure/UI, and `contexts/identity-access/` for auth domain/application/infrastructure/presentation.
- Auth API calls should stay behind IdentityAccess application ports and infrastructure gateways; React components must not contain raw HTTP calls.
- Memory and docs mention a current-account endpoint, but memory is context only and current code is stronger evidence.

## Current Code Facts

- `LoginController` returns `accessToken`, `tokenType`, `expiresIn`, and embedded `account`.
- `RefreshAuthenticationController` returns only `accessToken`, `tokenType`, and `expiresIn`.
- `RegisterAccountController` returns only `accountId` and `status`.
- `security.yaml` exposes login, refresh, logout, and register as public auth routes; no `/api/auth/me` route is present.
- Frontend `httpRequest` already sends `credentials: 'include'` and attaches `Authorization: Bearer <accessToken>` when an access token is supplied.
- Frontend refresh/logout gateway calls already send `X-CSRF-Intent: auth-refresh`.

## Conflicts Between Memory and Code

- Memory/design artifacts mention `/api/auth/me`, but current backend code does not implement `/api/auth/me`.
- Frontend previously called `/api/auth/me` after login and refresh. This pass removed that dependency.
- Planning expected register data shaped like account fields in some docs, but current backend returns `accountId` and `status`.

## Contract Decisions

- Login session is built from the current backend login response and its embedded `account`.
- Refresh/bootstrap no longer calls `/api/auth/me`. Because the current backend refresh response has no account payload, refresh creates a token-only session with `account: null`.
- `AuthSession.account` is now nullable to represent a refreshed session before a supported current-account endpoint exists.
- `getMe()` was removed from the frontend auth gateway port and HTTP adapter.
- Register mapping now uses `accountId` and `status`.
- Login and register request payloads still use `plainPassword`.
- Refresh and logout still send empty bodies with `X-CSRF-Intent: auth-refresh`.
- Cookie-dependent requests still rely on shared `credentials: 'include'`.
- Protected requests still use shared `Authorization: Bearer <accessToken>` support.

## Files Changed

- `frontend/src/contexts/identity-access/domain/auth-session.ts`
- `frontend/src/contexts/identity-access/application/ports/auth-gateway.ts`
- `frontend/src/contexts/identity-access/application/use-cases/login.ts`
- `frontend/src/contexts/identity-access/application/use-cases/refresh-session.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-api-contracts.ts`
- `frontend/src/contexts/identity-access/infrastructure/http/auth-http-gateway.ts`
- `docs/tasks/issues-49/frontend-implementation.md`
- `docs/tasks/issues-49/frontend-verification.md`
- `docs/tasks/issues-49/frontend-checklist.md`

## Remaining Scope

- Session Safety checklist remains mostly unchecked except where directly verified by this contract pass.
- Route work remains for `/app/dashboard`, `/pending-approval`, `/auth/session-loading`, and `/dashboard` redirect.
- UI/Tailwind/app shell work was intentionally not started in this pass.
