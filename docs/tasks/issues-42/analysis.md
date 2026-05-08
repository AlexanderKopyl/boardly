# Issue #42 Analysis: Create Frontend Next.js TypeScript Skeleton

## 1. Issue Summary

Issue #42 creates the initial Boardly frontend workspace under `frontend/` using Next.js, TypeScript,
and the context-based hexagonal architecture established in ADR-0006.

Implementation must start from the IdentityAccess/authentication foundation.
No Project, Issue, Board, Workflow, or Reporting UI is created in this issue.

The goal is a working skeleton that passes typecheck, lint, and build before any real authentication
behavior is implemented.

## 2. Files Inspected

| File | Reason |
|------|--------|
| `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` | Frontend/backend boundary, Symfony API direction, repository structure rules |
| `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` | Auth token constraints, forbidden storage, account lifecycle, API contracts |
| `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md` | Frontend layer rules, IdentityAccess context structure, forbidden patterns |
| `frontend/README.md` | Current workspace state, implementation guidance, acceptance criteria |
| `frontend/` directory listing | Confirmed only `README.md` exists — no initialized app |
| Repository root file listing | Confirmed no JavaScript lock files (`composer.lock`, `symfony.lock` only) |
| `docs/tasks/issues-39/` | Documentation style reference |

No backend source code was inspected. No additional bounded contexts were inspected.

## 3. ADR Requirements Discovered

### ADR-0004: API-First Backend / Frontend Boundary

- Next.js is the primary product UI.
- Frontend must not enforce business invariants as source of truth.
- Frontend must not decide workflow transitions or permissions.
- Raw `fetch` calls must not be scattered in React components.
- Frontend must use an API client layer.
- Symfony remains authoritative for permissions, workflow, and state changes.
- Repository structure: Symfony at root, frontend under `frontend/`.

### ADR-0005: JWT Access Token / HttpOnly Refresh Cookie Constraints

- JWT access tokens: short-lived (15 min), returned in JSON, stored **in memory only**.
- Refresh tokens: opaque, stored in `HttpOnly Secure` cookie, **never returned in JSON**,
  **never readable by JavaScript**.
- Frontend must call `POST /api/auth/refresh` on page reload (bootstrap).
- Frontend must call `POST /api/auth/logout` and clear local auth state on logout.
- Credentials must be included in all auth/cookie-dependent requests.
- Registration returns `201` with `pending_approval` status; no tokens issued.
- Only `active` accounts receive JWT access tokens.

Login/refresh response shape:

```json
{ "accessToken": "jwt", "tokenType": "Bearer", "expiresIn": 900 }
```

Registration response shape:

```json
{ "id": "...", "email": "...", "name": "...", "status": "pending_approval" }
```

### ADR-0006: Context-Based Hexagonal Architecture

- Source structure: `src/app/`, `src/shared/`, `src/contexts/<context-name>/`.
- Each context has `domain/`, `application/`, `infrastructure/`, `presentation/` layers.
- `domain/`: No React, no Next.js, no HTTP, no storage imports.
- `application/`: Orchestrates flows via ports; no raw `fetch`, no React, no storage.
- `infrastructure/`: HTTP gateways and state adapters; may use `shared/api` and `shared/config`.
- `presentation/`: React components and hooks; calls use cases, not backend directly.
- Access token must be memory-only.
- Refresh token must never be JavaScript-readable.
- First context: `identity-access`.

## 4. Current Frontend Workspace State

```text
frontend/
  README.md    ← only existing file
```

- No `package.json` exists.
- No lock file exists.
- No Next.js app initialized.
- No `.env.example`.
- Workspace is clean and ready for initialization.

## 5. Package Manager Recommendation

**Recommendation: npm**

Justification:

- No JavaScript lock file exists at repository root or under `frontend/`. There is no
  `pnpm-lock.yaml`, `yarn.lock`, or `package-lock.json` anywhere in the repository.
- `npm view` was used to confirm package versions (see §6), indicating npm is available.
- npm is the standard baseline. No team preference for pnpm or yarn has been established
  by any ADR, README, or existing tooling file.
- Using npm keeps tooling minimal and aligned with the default create-next-app output,
  minimizing future contributor friction.

If the team later decides to adopt pnpm for workspace performance or strict dependency isolation,
that can be introduced via a separate ADR or issue.

Expected lock file: `frontend/package-lock.json`.

## 6. Latest Stable Version Check

Version check executed via `npm view <package> version` on 2026-05-08:

| Package | Latest Stable Version |
|---------|----------------------|
| `next` | **16.2.6** |
| `react` | **19.2.6** |
| `react-dom` | **19.2.6** |
| `typescript` | **6.0.3** |
| `eslint` | **10.3.0** |
| `@types/react` | **19.2.14** |
| `@types/node` | **25.6.2** |

Commands used:

```bash
npm view next version
npm view react version
npm view react-dom version
npm view typescript version
npm view eslint version
npm view @types/react version
npm view @types/node version
```

**These versions must be re-confirmed immediately before `npm install` during implementation**,
as registry versions may advance between planning and execution.

Additional packages required (versions must be confirmed at implementation time):

- `@types/react-dom`
- `eslint-config-next`

## 7. Initialization Strategy

**Recommendation: Manual initialization**

`create-next-app` into a temporary directory and copying is rejected.

Justification:

- `create-next-app` creates a demo app with placeholder content (`app/page.tsx`, sample styles,
  sample assets) that would need to be deleted immediately.
- `create-next-app` creates a flat source structure, not the context-based hexagonal structure
  required by ADR-0006.
- Manual initialization gives full control over every file placed under `frontend/`.
- `frontend/README.md` is preserved naturally — it already exists and manual init does not
  overwrite it.
- The full target file list is explicitly known from ADR-0006 and the issue context.
  There is no ambiguity that would justify using `create-next-app` as a scaffold starting point.
- Manual initialization is a direct implementation of the documented plan, not a cleanup
  exercise on a generated skeleton.

Manual initialization sequence:

1. Write `frontend/package.json` with all dependencies and scripts.
2. Run `npm install` inside `frontend/`.
3. Write `frontend/tsconfig.json`.
4. Write `frontend/next.config.ts`.
5. Write `frontend/eslint.config.mjs`.
6. Write `frontend/.env.example`.
7. Create `frontend/src/` structure file by file per the plan.

## 8. Required Frontend Directory Structure

```text
frontend/
  package.json
  package-lock.json          ← generated by npm install
  next.config.ts
  tsconfig.json
  .env.example
  eslint.config.mjs
  README.md                  ← preserve (already exists)

  src/
    app/
      layout.tsx
      providers.tsx
      page.tsx
      login/
        page.tsx
      register/
        page.tsx
      dashboard/
        page.tsx

    shared/
      config/
        env.ts
      api/
        http-client.ts
        api-error.ts
      ui/
        Button.tsx
        Input.tsx

    contexts/
      identity-access/
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

## 9. Auth / Token Constraints

Per ADR-0005 and ADR-0006:

| Constraint | Rule |
|-----------|------|
| Access token storage | Memory only — module-level variable in `auth-memory-store.ts` |
| Refresh token storage | HttpOnly Secure cookie — managed by browser, never read by JavaScript |
| `localStorage` | Forbidden |
| `sessionStorage` | Forbidden |
| `IndexedDB` | Forbidden |
| JavaScript-readable cookies | Forbidden |
| URL params | Forbidden |
| Persisted Zustand/Redux storage | Forbidden |
| `NEXT_PUBLIC_*` env vars for tokens | Forbidden |
| Real auth behavior in this issue | Not implemented — stubs only |
| Token persistence | Not implemented |

The skeleton creates only stubs and type definitions. No real login, register, or refresh behavior
is implemented in this issue.

The shared HTTP client must include `credentials: 'include'` for all requests to enable
the browser to send the HttpOnly refresh cookie automatically.

## 10. Risks, Blockers, and Open Questions

### `next lint` deprecation (confirmed, documented)

In Next.js 16.x, `next lint` is deprecated and replaced with the ESLint CLI directly.

Confirmed via context7 (Next.js 16.1.6 docs) and the `next-lint-to-eslint-cli` codemod:

```json
{
  "scripts": {
    "lint": "eslint ."
  }
}
```

This is documented in the plan. The `lint` script must use `eslint .`, not `next lint`.

### Risks

| Risk | Mitigation |
|------|-----------|
| TypeScript 6 strict mode may reject patterns from TS 5 assumptions | Skeleton uses simple interfaces only; risk is low |
| `@types/react-dom` version not explicitly checked | Re-confirm at implementation time |
| `eslint-config-next` compatibility with ESLint 10 | Re-confirm at implementation time |
| ESLint flat config differs from `.eslintrc.*` format | `eslint.config.mjs` shape confirmed via context7 — see plan §4 |

### Open Questions

| Question | Status |
|---------|--------|
| Component library choice | Deferred — not in scope for this issue |
| Data-fetching library (TanStack Query, SWR) | Deferred — not in scope for this issue |
| State management library (Zustand, Redux Toolkit) | Deferred — not in scope for this issue |
| ESLint import boundary rules | Deferred — may be added after identity-access is stable |
| SSR vs CSR per page | Deferred — login/register/dashboard are initially client-side only |
| Real-time UI updates | Deferred — not in scope for this issue |
| pnpm vs npm long-term | Open — npm chosen for now, may revisit |

### Stop Conditions Assessment

| Condition | Status |
|----------|--------|
| Required ADR files missing | Not triggered — all three ADRs exist and are accepted |
| `frontend/README.md` missing | Not triggered — file exists |
| ADR-0006 contradicts issue structure | Not triggered — ADR-0006 specifies the exact structure used |
| `frontend/` already contains a different initialized app | Not triggered — only `README.md` exists |
| Package manager conflicts with existing lock files | Not triggered — no JavaScript lock files in repo |
| Latest package versions cannot be checked | Not triggered — versions confirmed via npm registry |
| Next.js lint command behavior unclear | Not triggered — confirmed `eslint .` via context7 |
| Skeleton would require deleting existing files | Not triggered — `README.md` is preserved, nothing deleted |
| Skeleton would require backend changes | Not triggered — skeleton has no API calls with real behavior |

**No stop conditions were triggered. Implementation is ready.**
