# Issue 61 Onboarding

## Task contract

- Branch: `feature/61-set-up-tailwind-css-and-migrate-existing-frontend-styling`
- Goal: set up Tailwind CSS and migrate the existing frontend styling baseline safely toward token-based styling and shared primitives.
- Source contract used: user brief for issue #61 plus current repo evidence.
- Note: no local GitHub issue body was present in the repository, so this onboarding state is based on the branch brief and codebase inspection.

## Guidance loaded

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

## Relevant files reviewed

- `frontend/package.json`
- `frontend/package-lock.json`
- `frontend/postcss.config.mjs`
- `frontend/tailwind.config.ts`
- `frontend/src/app/layout.tsx`
- `frontend/src/app/globals.css`
- `frontend/src/app/page.tsx`
- `frontend/src/app/login/page.tsx`
- `frontend/src/app/register/page.tsx`
- `frontend/src/app/pending-approval/page.tsx`
- `frontend/src/app/dashboard/page.tsx`
- `frontend/src/app/app/layout.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/app/app/dashboard/page.tsx`
- `frontend/src/app/app/projects/page.tsx`
- `frontend/src/app/app/projects/new/page.tsx`
- `frontend/src/app/app/projects/[projectId]/page.tsx`
- `frontend/src/app/auth/session-loading/page.tsx`
- `frontend/src/shared/lib/cn.ts`
- `frontend/src/shared/ui/*.tsx` selected primitives
- `frontend/src/contexts/identity-access/**`
- `frontend/src/contexts/projects/presentation/ui/*.tsx`

## Current facts

- The frontend workspace currently ships only `next`, `react`, and `react-dom` in `frontend/package.json`.
- `frontend/package-lock.json` does not include Tailwind, Radix, class-variance-authority, clsx, or tailwind-merge packages.
- `frontend/postcss.config.mjs` is empty.
- `frontend/tailwind.config.ts` already exists and maps semantic color tokens to CSS variables, but it is not backed by installed Tailwind packages yet.
- `frontend/src/app/layout.tsx` already imports `frontend/src/app/globals.css`, so there is a single global CSS entrypoint.
- `frontend/src/app/globals.css` currently contains semantic CSS variables plus a large amount of page/component-specific styling for auth screens, the app shell, dashboard screens, alerts, badges, empty states, sidebar nav, skeletons, and forms.
- The shared UI primitives are still custom CSS class driven rather than Tailwind-based.
- The projects slice already contains Tailwind utility class strings in component markup, so the styling migration will need the Tailwind pipeline in place before those screens behave as intended.

## Working note

- This task state is intentionally resumable and does not include an implementation checklist yet.
