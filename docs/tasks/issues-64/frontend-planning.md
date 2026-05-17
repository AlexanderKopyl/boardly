# Frontend Planning: Issue 64 - Redesign Projects List Using Stitch Reference

## Task Scope

Redesign the `/app/projects` Projects List page to align with the local Stitch reference in `docs/design/stitch/projects-list` while preserving current frontend routes, backend API contracts, auth/session behavior, and project action behavior.

The redesign is a presentational slice centered on `ProjectsListPage`. The implementation should use existing Tailwind CSS variables and shared primitives where they fit, and keep project-specific composition inside the Projects context presentation layer.

## Out-of-Scope Items

- Backend, Symfony controller, DTO, permission, lifecycle, or persistence changes.
- API contract changes or new list fields.
- Auth/session/token storage or refresh behavior changes.
- New UI libraries, CDN scripts, hosted fonts, hosted images, or Material Symbols imports from the Stitch HTML.
- Create Project page redesign.
- Project Details page redesign.
- Workspace sidebar redesign beyond what is already provided by the protected shell.
- Search, owner filters, owner names, and stats cards as real data features unless matching data/contracts already exist.

## Guidance Loaded

- [x] `AGENTS.md`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [ ] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` (not loaded; this slice does not change auth/session behavior)
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- [x] `docs/tasks/issues-64/onboarding.md`
- [x] `docs/tasks/issues-64/context-pack.md`
- [x] `docs/tasks/issues-64/context-budget.md`
- [x] `docs/design/stitch/projects-list/README.md`
- [x] `docs/design/stitch/projects-list/DESIGN.md`
- [x] `docs/design/stitch/projects-list/code.html` targeted list/header/state sections
- [x] `docs/design/stitch/projects-list/screen.png`

## Current Repository Facts

- Branch: `feature/64-redesign-projects-list-using-stitch-reference-design`.
- `/app/projects` is `frontend/src/app/app/projects/page.tsx` and renders `ProjectsListPage`.
- Primary component: `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Existing list fields available to presentation are `id`, `name`, `iconKey`, `status`, and `createdAt`.
- Current state handling already covers loading, ready, empty, error, active archive/delete confirmation, pending action, and inline action error.
- Current list behavior uses `listProjectsUseCase`, `archiveProjectUseCase`, `deleteProjectUseCase`, and `useProjectsHttpGateway`.
- Current shared primitives in use include `Button`, `Card`, `Badge`, `Alert`, `EmptyState`, `PageHeader`, and `Skeleton`.
- Current app shell already provides the protected workspace layout and sidebar.
- Working tree already had unrelated/untracked entries before this planning pass: `Library/` and local Stitch artifacts under `docs/design/stitch/projects-list/`.
- MemPalace was not used because current repository/design evidence was sufficient.

## Files Likely to Edit During Implementation

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`

Conditional only if a generic primitive gap is proven:

- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`
- `frontend/src/shared/ui/Alert.tsx`

## Files Likely Not to Edit

- `frontend/src/app/app/projects/page.tsx`
- `frontend/src/app/app/layout.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/app/globals.css`
- `frontend/tailwind.config.ts`
- `frontend/package.json`
- `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts`
- `frontend/src/contexts/projects/application/**`
- `frontend/src/contexts/projects/domain/**`
- `src/Boardly/**`
- `templates/**`
- `config/routes.yaml`
- `vendor/**`, `var/cache/**`, `node_modules/**`, `frontend/.next/**`, generated/build artifacts, and secrets

## Design Tokens to Use

Use existing Boardly tokens from `frontend/src/app/globals.css` and Tailwind mappings from `frontend/tailwind.config.ts`:

- Surfaces: `bg-background`, `bg-card`, `bg-muted`, `bg-secondary`, `bg-accent`.
- Text: `text-foreground`, `text-muted-foreground`, `text-primary`, `text-secondary-foreground`.
- Borders/focus: `border-border`, `border-input`, `ring-ring`, `focus-visible:ring-ring`.
- Actions/status: `bg-primary`, `text-primary-foreground`, `bg-destructive`, `text-destructive`, `text-success`, `text-warning`.
- Shapes: prefer token-backed `rounded-lg`, `rounded-md`, `rounded-sm`, and `rounded-full` for badges.
- Density: keep the existing `14px` base size; use compact row spacing inspired by Stitch.

Do not introduce raw color sprawl. If a one-off color is required, prefer existing CSS variables or local Tailwind opacity over new global tokens.

## Frontend Architecture Boundaries From ADR-0006

- `frontend/src/app` owns Next.js routing and composition only; no raw API calls or state logic should be added there.
- `frontend/src/contexts/projects/presentation` owns the Projects screen UI and local display state composition.
- `frontend/src/contexts/projects/application` use cases remain the boundary for list/archive/delete flows.
- `frontend/src/contexts/projects/infrastructure` remains responsible for HTTP contract mapping.
- `frontend/src/shared/ui` can host only generic context-free primitives.
- Frontend must not become authoritative for permissions, lifecycle transitions, persistence, or project state.

## Styling/Shared Primitive Constraints From ADR-0007

- Tailwind CSS and CSS variables remain the styling system.
- Shared UI primitives stay project-owned, generic, and context-free.
- Context-specific layout belongs in `ProjectsListPage`, not in `shared/ui`.
- Do not introduce a heavy component library or another styling system.
- Do not copy Stitch CDN scripts, Google fonts, external images, or Material Symbols imports.
- Add or change shared primitives only if the change is generic and reduces repeated UI complexity.

## Proposed Implementation Blocks

### 1. Page Shell Alignment If Needed

Files to touch:

- Preferred: none.
- Conditional: `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.

Exact visual goal:

- Keep the screen inside the existing protected workspace shell.
- Align page content with the Stitch desktop rhythm: spacious top header, constrained main column, soft slate background, and a clear white list area.
- Avoid changing the global shell/sidebar because the task is the Projects List page.

Expected risk:

- Low. Risk appears only if implementation tries to move shell concerns into the route/layout or duplicates the top nav/sidebar from Stitch.

Verification steps:

- Confirm `/app/projects` still renders through the existing protected shell.
- Confirm no route or layout file changed unless explicitly justified.
- Manual desktop/mobile check for page padding and no horizontal overflow.

Rollback note:

- Revert local shell/layout class changes in `ProjectsListPage`; leave shared app shell untouched.

### 2. Projects Page Header

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.

Exact visual goal:

- Create a compact but prominent header matching Stitch intent: title `Projects`, short workspace description, primary `Create project` action aligned to the right on desktop and wrapped cleanly on mobile.
- Preserve the ready-state project count badge using existing list length.
- Do not add real search or breadcrumb behavior from Stitch unless existing contracts already support it.

Expected risk:

- Low. The main risk is adding non-functional search/filter controls that imply unavailable behavior.

Verification steps:

- Create button still routes to `/app/projects/new`.
- Header wraps cleanly below `640px`.
- Count uses `viewState.projects.length` only when ready.

Rollback note:

- Restore previous `PageHeader` composition and `headerActions`.

### 3. List/Card Layout

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Conditional: `frontend/src/shared/ui/Card.tsx` only if a generic lower-radius card variant is necessary.

Exact visual goal:

- Replace disconnected large cards with a denser list surface inspired by Stitch: white rows, hairline borders, compact vertical rhythm, left project identity token, project name as primary link, created timestamp as metadata, status/actions on the right.
- Use only available data: `iconKey`, `name`, `createdAt`, `status`, `id`.
- Omit owner names and project metrics because they are not available in the list contract.

Expected risk:

- Medium. The dense row layout can overflow with long names, long icon keys, or visible confirmation content.

Verification steps:

- Long project names wrap or truncate without hiding actions.
- Detail link still targets `/app/projects/${project.id}`.
- Rows remain keyboard navigable and links/buttons remain real interactive elements.
- No fake owner/stat data appears.

Rollback note:

- Revert the ready-state section to the previous card-per-project layout while preserving helper functions and state.

### 4. Status Badges/Actions

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Conditional: `frontend/src/shared/ui/Badge.tsx` or `frontend/src/shared/ui/Button.tsx` only for generic variant/density changes.

Exact visual goal:

- Keep status badges visible but secondary to the project name, using existing `Badge` variants from `getStatusVariant`.
- Keep archive/delete actions grouped at row level, with destructive action styling secondary until confirmation.
- Preserve inline archive/delete confirmation and inline action error close to the affected project row.

Expected risk:

- Medium. Styling must not weaken disabled/loading guards or make destructive actions too easy to trigger accidentally.

Verification steps:

- Active projects show archive and delete controls.
- Non-active projects only show applicable current controls.
- Pending action disables other row actions as currently implemented.
- Confirm/cancel behavior and inline action errors remain row-local.

Rollback note:

- Restore previous action block markup from `ProjectsListPage`.

### 5. Loading/Empty/Error States

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Conditional: `frontend/src/shared/ui/EmptyState.tsx`, `frontend/src/shared/ui/Skeleton.tsx`, `frontend/src/shared/ui/Alert.tsx`.

Exact visual goal:

- Loading uses the same list shell and row geometry as the ready state with skeleton rows.
- Empty state uses a centered, dashed or quiet bordered surface with create action.
- Error state uses the same calm state frame with retry action and safe user-facing message.
- Keep state copy truthful to Boardly and existing behavior.

Expected risk:

- Low to medium. Empty/error states can drift away from the actual state machine if implementation creates separate loading paths.

Verification steps:

- Loading state renders before list resolution.
- Empty state renders when `result.projects.length === 0`.
- Error state retry sets loading and increments reload token.
- No raw exception/stack output is rendered.

Rollback note:

- Restore previous `ProjectsListSkeleton` and `EmptyState` usages.

### 6. Responsive Behavior

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.

Exact visual goal:

- Desktop rows scan left-to-right like Stitch.
- Narrow layouts stack project metadata/status/actions without horizontal scrolling.
- Header actions wrap cleanly.
- Confirmation and action errors stay visually attached to the selected project.

Expected risk:

- Medium. Dense desktop rows often fail on mobile if status/actions remain forced into one line.

Verification steps:

- Check around mobile width, tablet width, and desktop width.
- Confirm no horizontal page scroll.
- Confirm button text and badges fit their containers.
- Confirm focus rings remain visible after wrapping.

Rollback note:

- Relax the row layout back to stacked cards for mobile first, then reapply desktop-only row alignment.

### 7. Final Polish

Files to touch:

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`.
- Conditional shared primitives only if earlier blocks justified them.

Exact visual goal:

- Match Stitch's professional dense product feel using Boardly tokens: soft background, white content surfaces, hairline borders, subdued metadata, strong primary CTA, compact rows.
- Remove any non-functional Stitch-only controls before completion.
- Keep copy, actions, and data aligned with existing product behavior.

Expected risk:

- Low. Main risk is overfitting to the standalone Stitch HTML and adding unavailable features.

Verification steps:

- Run frontend typecheck, lint, and build.
- Manual smoke `/app/projects`, create navigation, detail navigation, archive/delete confirmation/cancel, responsive states.
- Visual review against `screen.png` for hierarchy, not pixel-perfect duplication.

Rollback note:

- Revert final local styling refinements separately from behavioral preservation changes.

## Implementation Checklist for Next Step

- [ ] Keep `/app/projects` route composition unchanged and scope implementation to Projects presentation.
- [ ] Rework the Projects page header around the Stitch hierarchy while preserving create navigation and ready-state count.
- [ ] Rebuild the ready-state list as a dense row/list surface using only `id`, `name`, `iconKey`, `status`, and `createdAt`.
- [ ] Preserve row-level detail links, archive/delete actions, inline confirmation, pending disabled state, and inline action errors.
- [ ] Restyle loading skeletons to match the final row/list geometry.
- [ ] Restyle empty and error states without changing their state transitions or safe messages.
- [ ] Verify responsive wrapping and no horizontal overflow across mobile/tablet/desktop widths.
- [ ] Avoid backend/API/auth/session/package/global style changes unless a documented blocker appears.
- [ ] Run `npm run typecheck`, `npm run lint`, and `npm run build` in `frontend/`.
- [ ] Manually smoke `/app/projects`, create navigation, detail navigation, archive/delete confirmation/cancel, and retry behavior.

## Validation Checklist

- [ ] `npm run typecheck` in `frontend/`
- [ ] `npm run lint` in `frontend/`
- [ ] `npm run build` in `frontend/`
- [ ] Manual smoke: `/app/projects`
- [ ] Manual smoke: `/app/projects/new` navigation
- [ ] Manual smoke: `/app/projects/[projectId]` navigation
- [ ] Manual smoke: archive/delete confirmation, cancel, submit, pending disabled state, inline action error
- [ ] Manual smoke: loading, empty, and error states where feasible
- [ ] Manual responsive check: mobile, tablet, desktop

## Risks

- Stitch includes search, owner filters, owner names, stats cards, top navigation icons, and external visual assets that are not supported by the current Boardly contracts.
- A dense row design can create overflow if project names or icon keys are long.
- Shared primitive changes may be too broad for a one-screen redesign.
- Empty/error/loading state verification may require local data or controlled failure setup during implementation.

## Open Questions

- Should unavailable Stitch features such as search, owner filters, owner names, and stats cards be omitted entirely, or represented later after API/product scope is defined?
- Should shared primitives eventually support an explicit lower-radius/dense variant, or should this screen use local classes until repetition proves the need?

## Next Artifact

Expected next artifact: `docs/tasks/issues-64/frontend-analysis.md`.
