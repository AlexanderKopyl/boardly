# Onboarding: Issue 64 - Redesign Projects List Using Stitch Reference

## Task contract

- Task folder: `docs/tasks/issues-64`
- Branch: `feature/64-redesign-projects-list-using-stitch-reference-design`
- Goal: build minimal context before frontend planning for redesigning the Projects List page from the Stitch reference in `docs/design/stitch/projects-list`.
- Non-goals: no implementation, no application-code edits, no backend/Twig behavior changes, no route or data-contract changes, no new UI libraries.
- Done criteria: identify current route/component/frontend stack, likely files, reusable tokens/primitives, Stitch/design-system comparison, context budget, recommended next skills/subagents.

## Durable guidance loaded

- `AGENTS.md`
- `.codex/config.toml`
- `.codex/skills/repo-onboarding/SKILL.md`
- `.codex/skills/context-pack-builder/SKILL.md`
- `.codex/skills/context-budget-audit/SKILL.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`

## Repository state

- Branch confirmed with `/opt/homebrew/bin/git branch --show-current`: `feature/64-redesign-projects-list-using-stitch-reference-design`.
- `git status --short` via `/opt/homebrew/bin/git` showed untracked `docs/design/stitch/projects-list/DESIGN.md`, `docs/design/stitch/projects-list/code.html`, `docs/design/stitch/projects-list/screen.png`, and `Library/`.
- The default `git` command failed because the active Xcode command line tools path is broken: `xcrun: error: invalid active developer path`.
- No MemPalace lookup was used because this was simple repository/design discovery.

## Candidate files

| File | Reason | Confidence |
| --- | --- | --- |
| `frontend/src/app/app/projects/page.tsx` | Current Next.js Projects List route entry point. | High |
| `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` | Current Projects List screen component and state/action rendering. | High |
| `frontend/src/app/app/layout.tsx` | App route layout wrapping workspace pages. | High |
| `frontend/src/app/app/ProtectedWorkspaceShell.tsx` | Protected shell/sidebar composition used by `/app/projects`. | High |
| `frontend/src/app/globals.css` | Tailwind entrypoint and current CSS custom property tokens. | High |
| `frontend/tailwind.config.ts` | Tailwind token mapping to CSS variables. | High |
| `frontend/package.json` | Confirms Next/React/Tailwind stack and no extra UI libraries. | High |
| `frontend/src/shared/ui/Button.tsx` | Existing generic action primitive. | High |
| `frontend/src/shared/ui/Card.tsx` | Existing generic surface primitive. | High |
| `frontend/src/shared/ui/Badge.tsx` | Existing generic status/metadata primitive. | High |
| `frontend/src/shared/ui/PageHeader.tsx` | Existing page heading/actions primitive. | High |
| `frontend/src/shared/ui/EmptyState.tsx` | Existing empty/error state surface. | High |
| `frontend/src/shared/ui/Skeleton.tsx` | Existing loading primitive. | High |
| `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts` | Current frontend list response contract. | Medium |
| `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts` | Current frontend API endpoints and mapping for projects. | Medium |
| `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php` | Backend API controller for `/api/projects`; confirms data contract should be preserved. | Medium |
| `config/routes.yaml` | Confirms Symfony project controllers are attribute-loaded, not product page routing. | Medium |
| `templates/base.html.twig` | Confirms Twig exists only as a base template, not the Projects product UI. | Medium |
| `docs/design/stitch/projects-list/README.md` | Stitch retrieval metadata and Boardly layout map. | High |
| `docs/design/stitch/projects-list/DESIGN.md` | Provided Boardly design-system tokens/reference narrative. | High |
| `docs/design/stitch/projects-list/code.html` | Provided Stitch reference HTML/CSS. | High |
| `docs/design/stitch/projects-list/screen.png` | Visual reference image; likely useful for implementation/planning visual checks. | Medium |

## Files read

- `AGENTS.md`
- `.codex/config.toml`
- `.codex/skills/repo-onboarding/SKILL.md`
- `.codex/skills/context-pack-builder/SKILL.md`
- `.codex/skills/context-budget-audit/SKILL.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `frontend/package.json`
- `frontend/postcss.config.mjs`
- `frontend/tailwind.config.ts`
- `frontend/src/app/layout.tsx`
- `frontend/src/app/app/layout.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/app/app/projects/page.tsx`
- `frontend/src/app/globals.css`
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/contexts/projects/domain/project-summary.ts`
- `frontend/src/contexts/projects/application/use-cases/list-projects.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts`
- `frontend/src/shared/lib/cn.ts`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`
- `templates/base.html.twig`
- `config/routes.yaml`
- `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php` section containing create/list routes
- `docs/design/stitch/projects-list/README.md`
- `docs/design/stitch/projects-list/DESIGN.md`
- `docs/design/stitch/projects-list/code.html` targeted sections
- Existing `docs/tasks/issues-64/onboarding.md`, `docs/tasks/issues-64/frontend-planning.md`, and `docs/tasks/issues-64/analysis.md` to avoid preserving stale assumptions.

## Facts learned

### Current Projects List route/controller/template/component

- Product route: `frontend/src/app/app/projects/page.tsx`.
- Route behavior: thin Next.js page that renders `<ProjectsListPage />`.
- Current component: `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Shared/protected layout: `frontend/src/app/app/layout.tsx` wraps app pages in `ProtectedWorkspaceShell`.
- Shell behavior: `ProtectedWorkspaceShell` uses `ProtectedRoute`, `AppShell`, `SidebarNav`, `SidebarAccountCard`, and `LogoutButton`.
- Backend API controller: `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php`.
- Backend API routes used by the frontend: `GET /api/projects`, `POST /api/projects`, `GET /api/projects/{projectId}`, `POST /api/projects/{projectId}/archive`, `DELETE /api/projects/{projectId}`.
- Symfony route loading: `config/routes.yaml` imports project controllers by attributes under `src/Boardly/Projects/Interfaces/Http/Controller/`.
- Twig: `templates/base.html.twig` exists but is not the Projects product UI. ADR-0004 says Twig must not be used for the main product UI.

### Current frontend stack

- Primary frontend: Next.js App Router under `frontend/src/app`.
- React: `react` and `react-dom` `19.2.6`.
- Next.js: `16.2.6`.
- TypeScript: `6.0.3`, strict frontend rules apply.
- UI rendering: React client components where needed; `ProjectsListPage` is `'use client'`.
- CSS stack: Tailwind CSS `4.3.0` through `@tailwindcss/postcss`, semantic CSS custom properties in `frontend/src/app/globals.css`.
- Asset builder: Next.js build pipeline with PostCSS/Tailwind. Scripts are `next dev`, `next build`, `next start`, `eslint .`, `tsc --noEmit`.
- Shared layout/base template: Next root layout is `frontend/src/app/layout.tsx`; app workspace layout is `frontend/src/app/app/layout.tsx`; protected shell is `frontend/src/app/app/ProtectedWorkspaceShell.tsx`.
- Shared UI primitives/classes: `Button`, `Card`, `Badge`, `Alert`, `EmptyState`, `PageHeader`, `Skeleton`, `AppShell`, `SidebarNav`, `Input`, `FormField`, `PasswordInput`, plus `cn`.
- Twig/Stimulus/Turbo/Vue: no evidence of Stimulus, Turbo, Vue, Webpack Encore, or Vite in the targeted frontend/package/config search. Twig exists only as a Symfony base template.

### Current Projects List behavior

- Loads projects through `listProjectsUseCase({ gateway })` and `useProjectsHttpGateway()`.
- Maintains local states: loading, ready, empty, error, active archive/delete confirmation, pending action, action error.
- Create route is `/app/projects/new`.
- Detail route is `/app/projects/${project.id}`.
- Current list item data available to the UI: `id`, `name`, `iconKey`, `status`, `createdAt`.
- No owner, project metrics, or full-text search fields are present in the current list contract.
- Archive/delete behavior is already implemented through application use cases and gateway methods.
- Inline confirmations and inline per-project action errors already exist and should be preserved.

### Existing design tokens/classes/primitives to reuse

- `globals.css` defines semantic tokens: `--background`, `--foreground`, `--card`, `--primary`, `--secondary`, `--muted`, `--accent`, `--destructive`, `--success`, `--warning`, `--border`, `--input`, `--ring`, `--radius`, sidebar tokens, and navy scale.
- `tailwind.config.ts` maps core colors and radius to CSS variables.
- `Button` already supports `primary`, `secondary`, `outline`, `ghost`, `destructive`; sizes `sm`, `md`, `lg`; loading state.
- `Badge` already supports `neutral`, `info`, `success`, `warning`, `destructive`.
- `Card`, `PageHeader`, `EmptyState`, and `Skeleton` cover the existing surface/header/state needs.
- `AppShell` already gives a two-pane protected workspace layout with a sticky sidebar on desktop.

### Stitch reference and DESIGN.md comparison

- `docs/design/stitch/projects-list/DESIGN.md` defines a corporate/modern, dense, low-decoration product UI with midnight navy sidebar, slate-blue workspace, white content surfaces, hairline borders, 14px base type, 8px radius, and tokenized colors.
- Current repo tokens already approximate much of the provided design system: `--background #f7f9fd`, white cards, navy primary, muted metadata, sidebar navy, functional status colors, and token-backed Tailwind classes.
- Difference: existing shared `Card` uses `rounded-3xl`, while DESIGN.md prefers 8px rounded primary containers and higher-density rows.
- Difference: current Projects list renders separate card tiles; Stitch reference uses a more compact high-density row list on one dominant content surface.
- Difference: Stitch reference includes filter/search/owner affordances and summary panels, but current Boardly list contract exposes only project summary fields and no filter/search API. Planning should treat those as visual inspiration only unless backend contracts already support them.
- Difference: Stitch reference imports Material Symbols and Google-hosted fonts/images in standalone HTML. Boardly should not introduce new UI libraries or external icon/font dependencies for this task.
- Alignment: Stitch reference and README both preserve a strong header CTA, project identity token, project name as primary link, metadata, status badge, loading skeleton rows, empty state, and inline row-level action affordances.

## Still unknown

- Whether implementation should exactly match `screen.png` pixel-level layout or use `README.md`/`DESIGN.md` as the source of design intent.
- Whether the stale existing `frontend-planning.md` and `analysis.md` should be superseded later because they currently contain statements from before local Stitch artifacts existed.
- Whether the next planning step should include opening `screen.png` with image inspection for visual fidelity.

## Recommended next skills

- `frontend-task-planning`
- `frontend-task-analysis`
- `frontend-style-system`
- `frontend-ui-composition`
- `frontend-review-checklist` after planning/analysis or after implementation

## Recommended frontend subagents

- `frontend-ui-composition` for page composition, responsive layout, state surfaces, and accessible UI structure.
- `frontend-context-architect` for ADR-0006 boundary review if planning proposes moving code between `app`, `shared`, and `contexts/projects`.
- `frontend-reviewer` for final frontend architecture/style/API-boundary review.

## No implementation note

No application code was edited. Only task documentation artifacts were created or updated.
