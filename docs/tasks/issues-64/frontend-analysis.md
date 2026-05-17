# Frontend Analysis: Issue 64 - Redesign Projects List Using Stitch Reference

## Inputs Reviewed

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/tasks/issues-64/onboarding.md`
- `docs/tasks/issues-64/context-pack.md`
- `docs/tasks/issues-64/context-budget.md`
- `docs/tasks/issues-64/frontend-planning.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/design/stitch/projects-list/README.md`
- `docs/design/stitch/projects-list/DESIGN.md`
- `docs/design/stitch/projects-list/code.html` targeted page/header/list/state sections
- `docs/design/stitch/projects-list/screen.png`
- `frontend/src/app/app/projects/page.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/app/globals.css`
- `frontend/tailwind.config.ts`
- `frontend/package.json`
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts`
- `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts`
- `frontend/src/shared/ui/Button.tsx`
- `frontend/src/shared/ui/Card.tsx`
- `frontend/src/shared/ui/Badge.tsx`
- `frontend/src/shared/ui/PageHeader.tsx`
- `frontend/src/shared/ui/EmptyState.tsx`
- `frontend/src/shared/ui/Skeleton.tsx`

## Guidance Loaded

- [x] `AGENTS.md`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [ ] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` (not loaded; no auth/session changes are planned)
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

## Current Component Structure

- `frontend/src/app/app/projects/page.tsx` is a thin Next.js route that renders `<ProjectsListPage />`.
- `frontend/src/app/app/layout.tsx` wraps `/app/**` pages in `ProtectedWorkspaceShell`.
- `ProtectedWorkspaceShell` composes `ProtectedRoute`, `AppShell`, `SidebarNav`, `SidebarAccountCard`, and `LogoutButton`.
- `ProjectsListPage` is a `'use client'` component in `frontend/src/contexts/projects/presentation/ui`.
- `ProjectsListPage` currently owns local rendering helpers for action copy, created-at formatting, status label/variant mapping, and safe error message mapping.
- Data flow is presentation -> projects application use cases -> projects gateway -> shared HTTP client/backend API.

## Current State Handling in `ProjectsListPage`

`ProjectsListPage` currently uses local React state for:

- `viewState`: discriminated union with `loading`, `ready`, `empty`, and `error`.
- `reloadToken`: increments to refetch after retry or successful archive/delete.
- `activeAction`: selected project/action pair for inline archive/delete confirmation.
- `pendingAction`: selected project/action pair currently submitting.
- `actionError`: row-level archive/delete failure message.

State transitions:

- Initial state is `loading`.
- `listProjectsUseCase({ gateway })` resolves to `empty` when the project list is empty.
- Non-empty results resolve to `ready`.
- Loading failures resolve to `error` with a safe mapped message.
- Retry sets `loading` and increments `reloadToken`.
- Archive/delete clear the active confirmation, set pending state, call the use case, then reload the list on success.
- Archive/delete failures render an inline row alert.

This state handling should be preserved. The redesign should change markup/classes and state surfaces, not the data flow.

## Current Shared UI Primitives in Use

- `Button`: existing variants `primary`, `secondary`, `outline`, `ghost`, `destructive`; sizes `sm`, `md`, `lg`; supports `isLoading`.
- `Card`: generic rounded card with border/background/shadow; currently uses `rounded-3xl`, which is softer than the Stitch 8px direction.
- `Badge`: existing variants `neutral`, `info`, `success`, `warning`, `destructive`; used for project icon key, status, and count.
- `PageHeader`: generic page title/description/actions layout.
- `EmptyState`: generic centered empty/error surface.
- `Skeleton`: generic loading block.
- `Alert`: already imported and used in `ProjectsListPage` for inline action errors, though it was not reopened in this pass because current usage was clear from the component.

Shared primitive changes are not required for planning. If implementation needs a lower-radius dense surface, prefer local classes first unless the change is clearly generic.

## Current API/Data Limitations

Available project list fields:

- `id`
- `name`
- `iconKey`
- `status`
- `createdAt`

Current frontend contracts do not expose:

- project owner name
- owner/team filters
- search query support
- project counts by status
- completed/in-progress stats
- project descriptions
- issue/task counts
- last activity

Therefore Stitch search, owner filter, owner names, and stats cards are design-reference features only. Implementation should omit them, defer them as follow-up, or render only existing-data UI. Static non-interactive placeholders are not recommended unless explicitly accepted during implementation because they can imply unavailable product behavior.

## Visual Mismatch Checklist Against Stitch Reference

### Layout

- Current: page header followed by separate large cards.
- Stitch: existing protected-like sidebar, prominent page header, filter/count surface, dense row list, optional summary area.
- Plan: keep current protected shell, strengthen header/list hierarchy, omit unsupported filter/search/stats features.

### Spacing

- Current: `space-y-8`, card padding `p-5`, separate card rhythm.
- Stitch: tighter row rhythm, 16px/24px spacing, compact metadata.
- Plan: use compact rows and list spacing without making mobile cramped.

### Typography

- Current: `text-lg` project names and `text-sm` metadata.
- Stitch: 14px base, 17px panel titles, strong weight-based hierarchy.
- Plan: project name should remain primary, metadata smaller/muted, header strong but not marketing-scale.

### Colors

- Current tokens already approximate Stitch: `--background #f7f9fd`, white cards, navy primary, muted metadata, functional status colors.
- Stitch uses the same broad direction: soft slate workspace, white surfaces, navy primary/sidebar, muted secondary text.
- Plan: use existing tokens; do not introduce raw duplicate palette values.

### Cards/List Rows

- Current: each project is a separate large rounded card with internal action block.
- Stitch: dense bordered rows on a dominant white surface; icon token left, identity/metadata center, status/action right.
- Plan: redesign ready and loading states around row geometry using available fields only.

### Buttons/Actions

- Current: primary create button, row archive/delete buttons, inline confirmation buttons.
- Stitch: strong right-aligned primary CTA, row detail link, status badge, subdued row actions.
- Plan: preserve Boardly archive/delete behavior and detail navigation; keep destructive actions clear but not visually louder than project identity.

### Loading/Empty/Error States

- Current: loading skeleton cards; empty/error use `EmptyState`.
- Stitch: loading skeleton follows row layout; empty state uses a centered dashed/quiet surface.
- Plan: loading should mirror final row layout; empty/error should use a calm state frame with existing actions.

### Responsive Behavior

- Current: flex-wrap cards and actions; likely usable but less dense.
- Stitch desktop is fixed-fluid; mobile guidance says stack actions and avoid horizontal scroll.
- Plan: desktop rows scan horizontally; mobile rows stack identity/status/actions and keep confirmations row-local.

## Frontend Context/Layer Impact

- `app`: no changes expected; route remains thin.
- `contexts/projects/presentation`: main implementation area.
- `contexts/projects/application`: no changes expected; use cases already express list/archive/delete flows.
- `contexts/projects/infrastructure`: no changes expected; API contract mapping should remain unchanged.
- `shared/ui`: conditional only for generic primitive refinements.

No new frontend context is justified.

## API Contract Impact

No API contract changes are recommended.

The implementation must not add UI that requires unavailable data unless it is explicitly deferred or omitted. Owner/search/stats from Stitch should not drive backend changes in this redesign task.

## Auth/Session Impact

No auth/session changes are recommended.

Projects gateway remains responsible for using the current access token and refresh callback. Presentation must not read tokens, construct backend URLs, or implement refresh behavior.

## UI/Style System Impact

- Use Tailwind utility classes with existing semantic CSS variables.
- Keep global CSS unchanged unless a real token gap is discovered and documented.
- Do not copy Stitch standalone Tailwind config, CDN scripts, fonts, hosted images, or Material Symbols.
- Prefer local `ProjectsListPage` composition for one-screen layout.
- Treat the Stitch reference as hierarchy/density guidance, not a direct HTML transplant.

## Accessibility Impact

- Keep `Link` for detail navigation and `Button` for actions.
- Maintain visible focus rings through existing shared primitives and focus-visible styles.
- Do not make color the only status signal; status badges must keep text labels.
- Confirmation and error messages should remain close to the affected row.
- Loading states should provide visible feedback and avoid trapping interaction.

## Security/Privacy Impact

- Do not log or expose auth tokens, cookies, request details, or raw backend errors.
- Do not convert frontend route guards into security decisions.
- Backend remains authoritative for archive/delete permissions and lifecycle validation.
- Frontend only renders allowed current controls based on returned status and still depends on backend validation.

## Performance Impact

- No new data fetching strategy is needed.
- No virtualization is needed for this small redesign unless list size becomes a measured problem later.
- Avoid adding global state or duplicating server data into additional stores.
- Keep client component status unchanged because the page already needs interactions.

## Test Strategy

Implementation should verify:

- `npm run typecheck` in `frontend/`.
- `npm run lint` in `frontend/`.
- `npm run build` in `frontend/`.
- Manual `/app/projects` smoke in the protected shell.
- Manual create navigation to `/app/projects/new`.
- Manual project detail navigation to `/app/projects/[projectId]`.
- Manual archive/delete open, cancel, submit, pending disabled state, and inline error behavior.
- Manual ready/loading/empty/error state review where feasible.
- Manual responsive checks for mobile, tablet, and desktop widths.

## Risks and Constraints

- Stitch includes unsupported UI concepts: search, owner filters, owner names, topbar utilities, and stats cards.
- Current API fields are intentionally narrow; redesign must not invent data.
- Current `Card` radius is softer than Stitch's 8px design direction; changing it globally may affect unrelated screens.
- Dense rows can overflow with long names or icon keys.
- Confirmation content can make rows tall; this is acceptable if it remains visually attached and responsive.
- Empty/error state smoke testing may require controlled local data/failure setup during implementation.
- `Library/` is currently untracked and unrelated; do not touch it.

## Recommended Relevant Frontend Subagents for Implementation

- `frontend-ui-composition`: own the page header, list row layout, responsive behavior, and state surfaces.
- `frontend-reviewer`: review ADR-0006/ADR-0007 boundaries, API contract discipline, and UI regressions after implementation.
- `frontend-context-architect`: optional only if implementation proposes moving code across `app`, `shared`, and `contexts/projects`.

## Stop Conditions for Implementation

Stop before editing application code if any of these become true:

- The design requires owner/search/stat fields as real functionality.
- The redesign appears to need backend/API contract changes.
- The implementation needs a new UI library, icon package, CDN font, or external asset dependency.
- Shared primitive changes would alter unrelated screens without a clear generic benefit.
- The route/layout/protected shell must change to reproduce Stitch exactly.
- Auth/session/token behavior would need to move into presentation.
- Local verification reveals project action behavior changed instead of only presentation changing.

## Refined Checklist

- [ ] Implement only in `ProjectsListPage.tsx` unless a documented generic primitive gap appears.
- [ ] Omit or defer Stitch search, owner filters, owner names, and stats cards.
- [ ] Keep create/detail/archive/delete route and use-case behavior unchanged.
- [ ] Build the ready state around dense responsive rows with existing data only.
- [ ] Make loading skeleton rows match the final row geometry.
- [ ] Keep empty/error states truthful, calm, and actionable.
- [ ] Preserve row-local confirmation, pending disabled state, and inline action errors.
- [ ] Run typecheck, lint, build, and manual responsive/action smoke checks.

## Next Artifact

Expected next artifact: `docs/tasks/issues-64/frontend-checklist.md` and `docs/tasks/issues-64/frontend-implementation.md` via `frontend-task-implementation`.
