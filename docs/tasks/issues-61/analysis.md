# Frontend Analysis: Tailwind CSS setup and frontend styling migration for issue #61

## 1. Skill used

- `frontend-task-analysis`

## 2. Relevant files discovered

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
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
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Input.tsx`
- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/FormField.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/Alert.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`
- `frontend/src/shared/ui/PasswordInput.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/RegisterForm.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/SessionLoadingState.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/LogoutButton.tsx`
- `frontend/src/contexts/identity-access/presentation/ui/SidebarAccountCard.tsx`
- `frontend/src/contexts/identity-access/presentation/guards/ProtectedRoute.tsx`
- `frontend/src/contexts/identity-access/presentation/hooks/useAuth.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectCreateForm.tsx`

## 3. Current styling/package facts

- `frontend/package.json` currently contains only the app runtime and basic tooling:
  - `next` `16.2.6`
  - `react` `19.2.6`
  - `react-dom` `19.2.6`
  - `typescript`, `eslint`, `eslint-config-next`, and React/Node type packages
- `frontend/package-lock.json` confirms that Tailwind, Radix, class-variance-authority, clsx, and tailwind-merge are not installed.
- `frontend/postcss.config.mjs` is currently empty, so there is no Tailwind/PostCSS wiring.
- `frontend/tailwind.config.ts` already defines:
  - semantic color keys such as `background`, `foreground`, `card`, `primary`, `secondary`, `muted`, `accent`, `destructive`, `border`, `input`, and `ring`
  - a `navy.*` palette backed by CSS variables
  - token-based `borderRadius` values
- `frontend/src/app/layout.tsx` already imports `./globals.css`, so the app has one global stylesheet entrypoint.
- `frontend/src/app/globals.css` already contains CSS variables for the visual system, but it also owns a large number of page and component class definitions:
  - auth page shell and hero/panel styling
  - dashboard shell and widget styling
  - app shell/sidebar styling
  - shared primitive styling for button, input, card, badge, alert, form field, empty state, skeleton, page header, sidebar nav, password input, and app shell
- The stylesheet is not just token definitions; it is acting as the primary component styling source today.
- `frontend/src/shared/lib/cn.ts` is a minimal string join helper, not a richer variant/composition helper.
- Shared UI primitives exist, but they are still CSS-class driven and do not yet use a Tailwind utility + token composition model.

## 4. Existing UI/style migration targets

- The most obvious migration targets are the shared primitives in `frontend/src/shared/ui/`:
  - `Button`
  - `Input`
  - `Card`
  - `Badge`
  - `FormField`
  - `AppShell`
  - `PageHeader`
  - `SidebarNav`
  - `EmptyState`
  - `Alert`
  - `Skeleton`
  - `PasswordInput`
- The auth and onboarding screens should move to token-driven composition instead of bespoke global CSS:
  - `frontend/src/app/login/page.tsx`
  - `frontend/src/app/register/page.tsx`
  - `frontend/src/app/pending-approval/page.tsx`
  - `frontend/src/app/auth/session-loading/page.tsx`
  - `frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx`
  - `frontend/src/contexts/identity-access/presentation/ui/RegisterForm.tsx`
  - `frontend/src/contexts/identity-access/presentation/ui/SessionLoadingState.tsx`
- The protected app shell should be migrated carefully because it owns the highest-visibility chrome:
  - `frontend/src/app/app/layout.tsx`
  - `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
  - `frontend/src/contexts/identity-access/presentation/ui/SidebarAccountCard.tsx`
  - `frontend/src/contexts/identity-access/presentation/ui/LogoutButton.tsx`
- The dashboard screen is a clear migration target because it is currently a dense custom layout with many hardcoded class names and prototype-like styling decisions:
  - `frontend/src/app/app/dashboard/page.tsx`
- The projects slice is already written with Tailwind utility classes in the JSX, so it should become one of the first consumers after Tailwind is wired:
  - `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
  - `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx`
  - `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx`
  - `frontend/src/contexts/projects/presentation/ui/ProjectCreateForm.tsx`
- Styling migration should also shrink `frontend/src/app/globals.css` so it holds tokens, base/reset rules, and only the global layout rules that cannot live in shared primitives.

## 5. Explicit out-of-scope areas

- Backend API changes.
- Authentication/session model changes.
- Permission, workflow, or business invariant changes.
- Adding new product contexts beyond the currently relevant frontend slices.
- Broad product redesign or new visual identity work.
- Introducing Radix components where no accessibility-sensitive primitive is needed yet.
- Building a large design system beyond the primitives already required by repeated UI needs.
- Changing route structure or business flow unless a route must be touched to validate the styling migration.

## 6. Verification/tooling facts

- The existing verification scripts in `frontend/package.json` are:
  - `dev`
  - `build`
  - `start`
  - `lint`
  - `typecheck`
- There is no frontend test runner configured yet in `frontend/package.json`.
- There are no repo-owned frontend test files under `frontend/`.
- `frontend/tailwind.config.ts` already scans `./src/**/*.{js,ts,jsx,tsx,mdx}`, which covers the current app, shared, and context files once Tailwind is installed.
- `frontend/src/app/layout.tsx` already imports the global stylesheet, so the migration can be verified from the existing app entrypoint without changing route composition first.
- The projects pages already contain Tailwind utility class strings, so a build check after Tailwind wiring will immediately surface whether the setup is working.

## 7. Risks and blockers

- Tailwind is not installed yet, so the current utility class strings in the projects slice are effectively inert until the pipeline exists.
- `frontend/postcss.config.mjs` is empty, so the first implementation step has to choose and wire the actual Tailwind mode before any styling migration can be trusted.
- `frontend/src/app/globals.css` is already large and duplicated in places, which makes a wholesale rewrite risky; the migration should be phased.
- The current styling baseline mixes custom global CSS, semantic variables, and utility-class-like markup. Partial migration without a clear ordering could produce inconsistent pages.
- No frontend test framework exists yet, so regression checking will initially rely on `build`, `lint`, `typecheck`, and manual browser verification.
- The shared UI primitives are already used across auth, shell, dashboard, and projects. If they are migrated piecemeal, several pages can break at once unless the primitive contract stays stable.
- The decision between Tailwind v3-style PostCSS setup and Tailwind v4 CSS-first setup is still a blocker for exact implementation planning.

## 8. Recommended next step: planning

- Decide the exact Tailwind setup version and wiring model first.
- Reduce `globals.css` to semantic tokens, base styles, and true global layout rules.
- Migrate shared primitives next so the auth shell, protected shell, and project screens can consume a consistent styling contract.
- Then migrate the auth/app/dashboard/project routes to the new shared primitives and Tailwind utility classes.
- Keep implementation checklist creation for the planning/implementation step, not this analysis step.
