# Context Pack: Issue 64 - Redesign Projects List Using Stitch Reference

## Task contract

Build minimal context before frontend planning for redesigning the Projects List page using `docs/design/stitch/projects-list`. Do not implement. Preserve existing Symfony/Twig/backend behavior, frontend routes, API data contracts, and existing architecture boundaries.

## Discovery method

- Search-first from explicit user-provided paths and symbols.
- Targeted route/component search for `ProjectsListPage`, `/app/projects`, `/api/projects`, frontend package/config files, shared UI primitives, and Stitch artifacts.
- No whole-repository scan beyond bounded `rg --files`/`find` commands over relevant roots.
- No MemPalace lookup because repository facts were discoverable directly.

## Must-read

| Path | Reason | Load mode |
| --- | --- | --- |
| `AGENTS.md` | Required project operating rules, security constraints, architecture boundaries. | full file |
| `.codex/config.toml` | Project permissions, registered skills, frontend subagents, styling/architecture baseline. | full file or section summary |
| `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` | Confirms Next.js product UI and no main-product Twig UI. | section/full as needed |
| `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md` | Defines `app/shared/contexts` boundaries for any redesign planning. | section/full as needed |
| `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` | Defines Tailwind, CSS variables, shared primitive strategy, no alternate styling system. | section/full as needed |
| `docs/development/frontend/boardly-frontend-developer-rules.md` | Frontend implementation constraints: TypeScript, layer direction, API and presentation rules. | section/full as needed |
| `docs/design/stitch/projects-list/README.md` | Stitch retrieval metadata and Boardly-specific design interpretation. | full file |
| `docs/design/stitch/projects-list/DESIGN.md` | Provided design-system tokens, typography, spacing, component rules. | full file or token/component sections |
| `docs/design/stitch/projects-list/code.html` | Reference HTML/CSS for page structure, row treatment, loading/empty states. | section only: Tailwind config, page header, filters/list/row/state areas |
| `docs/design/stitch/projects-list/screen.png` | Visual reference for planning fidelity. | image inspection only if needed |
| `frontend/package.json` | Confirms frontend framework, dependencies, scripts, and no UI library dependency. | full file |
| `frontend/postcss.config.mjs` | Confirms Tailwind/PostCSS build path. | full file |
| `frontend/tailwind.config.ts` | Confirms token mapping and available Tailwind semantic classes. | full file |
| `frontend/src/app/layout.tsx` | Next root layout and global CSS import. | full file |
| `frontend/src/app/app/layout.tsx` | Workspace app layout wrapper. | full file |
| `frontend/src/app/app/ProtectedWorkspaceShell.tsx` | Current protected shell/sidebar composition around Projects List. | full file |
| `frontend/src/app/app/projects/page.tsx` | Current Projects List route entry. | full file |
| `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` | Primary target for future redesign; owns current state, actions, routes, UI. | full file |
| `frontend/src/app/globals.css` | Current CSS variables and Tailwind import. | full file |
| `frontend/src/shared/lib/cn.ts` | Existing class composition helper. | full file |
| `frontend/src/shared/ui/Button.tsx` | Existing generic button variants/loading state. | full file |
| `frontend/src/shared/ui/Card.tsx` | Existing generic card/surface primitive. | full file |
| `frontend/src/shared/ui/Badge.tsx` | Existing status/metadata primitive. | full file |
| `frontend/src/shared/ui/PageHeader.tsx` | Existing page header/action primitive. | full file |
| `frontend/src/shared/ui/EmptyState.tsx` | Existing empty/error state primitive. | full file |
| `frontend/src/shared/ui/Skeleton.tsx` | Existing loading primitive. | full file |
| `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts` | Current list data contract available to UI. | full file |
| `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts` | Confirms API paths and mapping remain unchanged. | section/full as needed |

## Maybe-read

| Path | Open only if | Reason |
| --- | --- | --- |
| `frontend/src/shared/ui/Alert.tsx` | Planning touches inline action or page-level error styling. | Existing error primitive used by `ProjectsListPage`. |
| `frontend/src/shared/ui/AppShell.tsx` | Planning proposes shell/main spacing changes. | Shell affects page width and background. |
| `frontend/src/shared/ui/SidebarNav.tsx` | Planning touches sidebar nav or active-route treatment. | Current reference includes sidebar patterns, but task is Projects List page. |
| `frontend/src/shared/ui/Input.tsx` | Planning proposes adding visible search/filter controls from reference. | Search/filter should not be added unless backed by a real contract or explicitly display-only. |
| `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx` | Need to compare detail navigation or shared action affordances. | Sibling project screen; not required for list-only redesign. |
| `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx` | Need to compare create navigation/header patterns. | Sibling project screen; not required for list-only redesign. |
| `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php` | Need exact backend API response behavior or route confirmation. | Backend should not change, but contract awareness prevents UI assumptions. |
| `src/Boardly/Projects/Interfaces/Http/Response/ListProjectsResponseDto.php` | Planning proposes fields not currently available in frontend contracts. | Confirms backend response fields. |
| `config/routes.yaml` | Need to prove Symfony route import behavior. | Already briefly checked. |
| `templates/base.html.twig` | Need to explain Twig non-involvement. | Already briefly checked. |
| `docs/tasks/issues-64/frontend-planning.md` | Need to reconcile previous planning artifact with new local Stitch files. | Existing artifact contains stale note that local Stitch export was absent. |
| `docs/tasks/issues-64/analysis.md` | Need to reconcile previous analysis artifact with new local Stitch files. | Existing artifact contains stale note that exact exported pixel content was unavailable. |

## Do-not-read

| Path/pattern | Reason |
| --- | --- |
| `vendor/**` | Dependency code; not needed for frontend planning. |
| `var/cache/**` | Generated/cache artifacts. |
| `node_modules/**`, `frontend/node_modules/**` | Dependency code. |
| build output directories such as `.next/**`, `dist/**`, `build/**` | Generated artifacts. |
| `.env*`, `frontend/.env*`, `**/.env*` | Secrets/local environment; forbidden by project rules. |
| `*.sql`, `**/*.sql`, dumps, local DB files, private keys | Forbidden sensitive artifacts. |
| unrelated backend bounded contexts | No evidence they affect the Projects List redesign. |
| broad full-repo file reads | Violates context discipline; targeted searches are enough. |

## Required guidance

- ADR-0004: Next.js is product UI; Symfony remains API/domain owner; Twig is not main product UI.
- ADR-0006: Keep route composition in `app`, generic primitives in `shared`, and project-specific UI in `contexts/projects/presentation`.
- ADR-0007: Use Tailwind and CSS variables; shared primitives remain project-owned and generic; do not introduce another broad styling system.
- Frontend developer rules: no raw API calls in presentation, no backend URL construction in React components, no leaking transport contracts into UI, preserve auth/session boundaries.

## Evidence gathered

- `/app/projects` is `frontend/src/app/app/projects/page.tsx`, which renders `ProjectsListPage`.
- `ProjectsListPage` is a client component that already implements loading, ready, empty, error, archive/delete confirmation, pending action, and inline action errors.
- Current list data fields are limited to `id`, `name`, `iconKey`, `status`, and `createdAt`.
- Frontend uses Next.js `16.2.6`, React `19.2.6`, TypeScript `6.0.3`, Tailwind CSS `4.3.0`, and PostCSS through `@tailwindcss/postcss`.
- No targeted evidence of Stimulus, Turbo, Vue, Vite, or Webpack Encore in frontend/package/config files.
- Existing CSS variables and Tailwind config already map most Boardly design tokens.
- Existing shared primitives cover actions, cards, badges, page header, empty/error surfaces, skeletons, app shell, and sidebar nav.
- Stitch `code.html` shows a fixed sidebar/top bar, strong Projects header with primary CTA, filters/search controls, compact project rows, status badges, skeleton rows, empty state, and summary/tip panels.
- `DESIGN.md` favors dense enterprise UI, 14px base type, hairline borders, low shadows, white list surfaces, 8px radius, navy primary/sidebar, and tokenized semantic colors.

## Loading order

1. Stable guidance: `AGENTS.md`, ADR-0004, ADR-0006, ADR-0007, frontend developer rules.
2. Design sources: `docs/design/stitch/projects-list/README.md`, `DESIGN.md`, targeted sections of `code.html`, optionally `screen.png`.
3. Frontend stack/config: `frontend/package.json`, `postcss.config.mjs`, `tailwind.config.ts`, `globals.css`.
4. Route/shell: `frontend/src/app/layout.tsx`, `frontend/src/app/app/layout.tsx`, `ProtectedWorkspaceShell.tsx`, `frontend/src/app/app/projects/page.tsx`.
5. Primary target: `ProjectsListPage.tsx`.
6. Shared primitives: `Button`, `Card`, `Badge`, `PageHeader`, `EmptyState`, `Skeleton`, then `Alert` only if needed.
7. API contract awareness: `projects-api-contracts.ts`, `projects-http-gateway.ts`, backend controller/DTO only if planning proposes additional data or API behavior.

## Next recommended skill

- `frontend-task-planning` first, using this context pack.
- Then `frontend-task-analysis` before implementation.
- Use `frontend-ui-composition` and `frontend-style-system` during planning/analysis.
- Use `frontend-review-checklist` after implementation or after a detailed plan.

## Recommended frontend subagents

- `frontend-ui-composition`: screen structure, state layout, responsive behavior, keyboard/accessibility review.
- `frontend-context-architect`: only if planning proposes moving code or adding primitives across `app/shared/contexts`.
- `frontend-reviewer`: final review for ADR-0006/0007, auth/API boundary, import direction, and tests.

## No implementation note

No application code was edited. This context pack is documentation-only.
