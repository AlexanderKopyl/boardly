# Issue 60 Onboarding

## Task contract

- Branch: `feature/60-implement-frontend-project-pages-and-actions`
- Goal: analyze the first frontend Projects slice and how it should fit Boardly's current frontend architecture.
- Source contract used: user brief for issue #60 plus repo evidence.
- Note: the GitHub issue body was not retrievable directly from local repo state, so this onboarding note is based on the branch name, the task brief, and the current codebase.

## Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/development/backend/boardly-symfony-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

## Relevant files reviewed

- `frontend/package.json`
- `frontend/src/app/layout.tsx`
- `frontend/src/app/page.tsx`
- `frontend/src/app/login/page.tsx`
- `frontend/src/app/register/page.tsx`
- `frontend/src/app/pending-approval/page.tsx`
- `frontend/src/app/auth/session-loading/page.tsx`
- `frontend/src/app/dashboard/page.tsx`
- `frontend/src/app/app/dashboard/page.tsx`
- `frontend/src/app/providers.tsx`
- `frontend/src/shared/api/http-client.ts`
- `frontend/src/shared/api/api-error.ts`
- `frontend/src/shared/config/env.ts`
- `frontend/src/shared/ui/*.tsx` selected primitives
- `frontend/src/contexts/identity-access/**`
- `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php`
- `src/Boardly/Projects/Interfaces/Http/Response/*.php`
- `src/Boardly/Projects/Interfaces/Http/OpenApi/Schema/*.php`
- `tests/Boardly/Projects/Interfaces/Http/Controller/ProjectControllerTest.php`

## Initial facts

- Only the `identity-access` frontend context exists today.
- There is no `frontend/src/contexts/projects/` yet.
- The frontend uses a global `AuthProvider` and a memory-only auth session store.
- The shared HTTP client already supports bearer access tokens, credentials-included requests, and 401 retry via callback.
- The backend Projects API already exists and is protected by bearer authentication.

## Open questions for planning

- Which first Projects screen is in scope: list, create, detail, or a shell for all three?
- Should the first slice expose a dedicated `/app/projects` route, or start inside the existing protected app shell?
- Should project loading reuse the shared HTTP client retry path or add a Projects-specific gateway wrapper with refresh fallback?
