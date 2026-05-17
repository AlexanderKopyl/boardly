# Frontend Analysis: Issue 64 - Redesign Projects List Using Stitch Reference

## Inputs reviewed

- `frontend/src/app/app/projects/page.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectCreateForm.tsx`
- `frontend/src/contexts/projects/application/use-cases/list-projects.ts`
- `frontend/src/contexts/projects/application/use-cases/archive-project.ts`
- `frontend/src/contexts/projects/application/use-cases/delete-project.ts`
- `frontend/src/contexts/projects/application/ports/project-gateway.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts`
- `frontend/src/contexts/projects/domain/project-summary.ts`
- `frontend/src/contexts/projects/domain/project-status.ts`
- `frontend/src/contexts/projects/domain/project-errors.ts`
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Input.tsx`
- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/Alert.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `frontend/src/shared/ui/FormField.tsx`
- `frontend/src/app/app/layout.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/app/globals.css`
- `frontend/package.json`
- `docs/tasks/issues-61/analysis.md`
- `docs/tasks/issues-61/frontend-planning.md`
- `docs/tasks/issues-61/frontend-verification.md`

## Guidance loaded

- [x] `AGENTS.md`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [ ] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

## Current state

### Route composition

- `/app/projects` is a thin route entry point that renders `ProjectsListPage`.
- The screen sits under the protected workspace shell, so it inherits the app layout, sidebar, and auth guard from `frontend/src/app/app/layout.tsx` and `frontend/src/app/app/ProtectedWorkspaceShell.tsx`.
- Sibling project routes already exist:
  - `/app/projects/new` renders `ProjectCreatePage`
  - `/app/projects/[projectId]` renders `ProjectDetailsPage`

### Projects list behavior

- `ProjectsListPage` is a client component with local state for:
  - loading
  - ready
  - empty
  - error
- It loads projects through `listProjectsUseCase({ gateway })`.
- It routes the create action to `/app/projects/new`.
- It renders a project card for each item with:
  - icon key badge
  - project name link to `/app/projects/[projectId]`
  - created-at timestamp
  - status badge
  - archive and delete actions
- It shows an inline confirmation card for the currently selected action.
- It disables actions while an archive/delete request is in flight.
- It shows per-project action errors inline with an alert.

### Error handling

- The screen maps `ProjectsError` codes to user-facing messages.
- Unauthorized and forbidden states are translated into session/access guidance.
- Missing projects and validation failures have separate messages.
- General failures fall back to generic load/update copy.

### API and gateway behavior

- The gateway uses `/api/projects`, `/api/projects/:id`, `/api/projects/:id/archive`, and `/api/projects/:id` endpoints.
- Access tokens are read from the auth boundary and refreshed through the gateway dependency, not from the presentation layer.
- The frontend screen does not own any backend business rules; it only renders and dispatches use cases.

## Frontend context/layer impact

- The redesign belongs in `contexts/projects/presentation/ui`.
- `frontend/src/app` should remain a thin route-composition layer.
- Shared UI primitives in `frontend/src/shared/ui` should remain generic; the projects list should not push project-specific behavior into shared components.
- The current projects screen already respects ADR-0006 boundaries reasonably well, so the redesign should focus on presentation and composition, not moving behavior into `app` or `shared`.

## Current styling/shared UI facts

- Issue #61 baseline is already present on this branch:
  - `frontend/package.json` includes Tailwind/PostCSS packages
  - `frontend/src/app/globals.css` already imports Tailwind and defines semantic tokens
- Shared primitives already use Tailwind utility classes and token-backed variants.
- The current projects list is mostly built from those shared primitives, with some screen-specific utility classes for spacing and confirmation layout.
- The current visual baseline is therefore not blocked on CSS infrastructure; it is ready for a screen redesign slice.

## Stitch reference retrieval status

- Stitch MCP retrieval succeeded.
- Retrieved screen:
  - Project ID: `6711793596249838566`
  - Screen ID: `db4fc82ad077451daab0feabc1138197`
  - Title: `Projects List`
  - Device: desktop
- Available artifacts reported by Stitch MCP:
  - screenshot download URL
  - HTML code download URL
- Exact exported pixel content was not directly rendered in this environment, so the reference currently contributes confirmed artifact availability, not a detailed visual readout.

## Stitch-to-Boardly design implications

- The current screen already has the same functional anatomy a redesign would need:
  - page header
  - create-project action
  - list of project cards
  - loading, empty, and error states
  - destructive lifecycle actions with confirmation
- That means the redesign likely needs to change hierarchy, spacing, typography, and visual emphasis more than business flow.
- Any Stitch-driven update should preserve:
  - protected shell composition
  - route destinations
  - archive/delete semantics
  - backend-owned permissions and lifecycle validation
- If local artifact export becomes available, it should be saved under `docs/design/stitch/projects-list/` with a screenshot, HTML export, and a short index/README.

## Architecture/scope boundaries

- In scope:
  - Projects list presentation redesign
  - shared primitive reuse if the reference needs stronger composition
  - state presentation for loading/empty/error/ready/action-confirmation
- Out of scope:
  - backend API changes
  - project permission rules
  - workflow changes
  - auth/session behavior
  - new route structure
  - new product context creation

## Risks/blockers

- The Stitch screen is reachable, but the tool output here did not expose the rendered pixels or parsed HTML inline, so exact fidelity planning is limited until the exported artifact is readable locally.
- The list screen already includes destructive actions and per-item confirmation. A redesign must preserve disabled/loading semantics so the UI does not allow duplicate archive/delete submissions.
- The projects list uses backend status strings directly. If the Stitch reference implies a stronger visual taxonomy, the mapping layer may need a presentation-only normalization step.
- There is no frontend test runner in the repo, so verification will still rely on build/lint/typecheck plus manual browser smoke tests later.

## Recommended next step: planning

- Proceed to planning for the `/app/projects` redesign slice.
- Planning should define:
  - which visual structure is taken from the Stitch reference
  - which shared primitives are reused or extended
  - how the list, empty, error, and confirmation states should be composed
  - which verification commands and manual checks will confirm the redesign without changing behavior

