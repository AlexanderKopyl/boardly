# Frontend Planning: Issue 64 - Redesign Projects List Using Stitch Reference

## Goal

Redesign `/app/projects` to follow the Stitch `Projects List` reference while preserving current Boardly projects behavior, routes, state handling, and backend ownership boundaries.

## Scope

- Projects list presentation only.
- Current list states: loading, empty, error, ready.
- Current archive/delete confirmation flow.
- Current create and detail navigation.
- Existing shared UI primitives as consumers, with only minimal reusable adjustments if a generic gap is unavoidable.

## Non-goals

- Backend API changes.
- Auth/session/token behavior changes.
- Create page redesign.
- Details page redesign.
- New frontend context creation.
- Broad Tailwind or global CSS migration beyond the issue #61 baseline.
- Changes to backend permissions, lifecycle rules, or project state ownership.

## Guidance loaded

- [x] `AGENTS.md`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [ ] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` (not needed; no auth/session changes are planned)
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- [x] `docs/tasks/issues-64/onboarding.md`
- [x] `docs/tasks/issues-64/analysis.md`
- [x] `docs/tasks/issues-61/analysis.md`
- [x] `docs/tasks/issues-61/frontend-planning.md`
- [x] `docs/tasks/issues-61/frontend-verification.md`

## Current repository facts

- `/app/projects` is still a thin Next.js route that renders `ProjectsListPage`.
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` owns the current projects list screen state machine.
- The screen already supports the required states: loading, empty, error, and ready.
- Archive and delete are already implemented as per-project actions with inline confirmation, loading guards, and inline action errors.
- Create navigation still points to `/app/projects/new`.
- Detail navigation still points to `/app/projects/[projectId]`.
- Issue #61 already established the Tailwind/CSS-variable/shared-primitive baseline, so this slice should not reopen global styling work.
- Local Stitch export files are not present under `docs/design/stitch/projects-list/`; only the analysis artifact metadata is available here, so the visual plan must stay tied to the current reference contract rather than a locally stored screenshot.

## Frontend context ownership

- No new context is justified.
- The redesign belongs in `contexts/projects/presentation`.
- `app` should stay limited to route composition.
- `shared` should remain generic and context-free.

## Layer/file placement

| Layer | Responsibility | Planned files |
| --- | --- | --- |
| `app` | No route changes expected; keep `/app/projects` as thin composition | none expected |
| `contexts/projects/presentation` | Main redesign of the Projects list screen, state surfaces, and local composition | `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` |
| `shared` | Only if the Stitch reference reveals a reusable gap that improves more than this screen | conditional: `frontend/src/shared/ui/Button.tsx`, `frontend/src/shared/ui/Card.tsx`, `frontend/src/shared/ui/Badge.tsx`, `frontend/src/shared/ui/Alert.tsx`, `frontend/src/shared/ui/EmptyState.tsx`, `frontend/src/shared/ui/PageHeader.tsx`, or `frontend/src/shared/ui/Skeleton.tsx` |

## Planning decisions

1. Preserve the current Projects state machine and use cases.
2. Treat the Stitch reference as a screen-structure and visual-density guide, not as a reason to change backend-owned behavior.
3. Keep navigation destinations unchanged.
4. Prefer local composition changes in `ProjectsListPage` over shared primitive edits.
5. Only touch shared primitives if the same change would be broadly useful and remains generic.
6. Use the issue #61 Tailwind/token baseline only; do not reintroduce global stylesheet coupling.

## Implementation order

1. Translate the Stitch reference into a Boardly layout map: page header, action area, list surface, item chrome, and state surfaces.
2. Recompose `ProjectsListPage` around that map while preserving current route targets and use-case calls.
3. Restyle loading, empty, error, and ready states to match the reference hierarchy and density.
4. Preserve archive/delete confirmation, disabled/loading guards, and inline action error presentation.
5. Review whether any shared primitive needs a minimal generic adjustment; keep that as a last resort.
6. Verify desktop and mobile behavior, then run the standard frontend checks and manual smoke tests.

## Files likely to change

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `docs/tasks/issues-64/frontend-planning.md`
- `docs/tasks/issues-64/frontend-checklist.md`
- Possibly one or more shared primitives if a generic, reusable gap is discovered during implementation.

## Stitch reference usage plan

- Use the Stitch Projects List reference to define the page's visual hierarchy first, then map Boardly behavior into that structure.
- Preserve the current Boardly action model, but align spacing, emphasis, and density with the reference.
- Match the reference's treatment of:
  - header and CTA placement
  - list/card hierarchy
  - metadata prominence
  - status presentation
  - confirmation affordances
  - empty/loading/error surfaces
- Do not copy product-specific wording from the reference if it conflicts with Boardly copy or backend-driven semantics.
- If the Stitch layout is more table-like than the current card list, keep the Boardly interaction model and adapt the visual container rather than changing behavior.

## Styling/shared UI boundaries

- Keep styling token-based and Tailwind-driven, following the issue #61 baseline.
- Avoid new global CSS and avoid broad styling-system changes.
- Prefer reusing existing shared primitives as-is.
- If a primitive needs adjustment, keep it generic and context-free.
- Do not push project-specific spacing, labels, or lifecycle semantics into `shared`.
- Keep destructive action styling and confirmation behavior local to the projects context unless another screen clearly needs the same primitive change.

## API/auth/session impact

- None expected.
- The projects gateway and backend-owned behavior remain unchanged.
- No token storage, refresh, or auth bootstrapping work is part of this slice.

## UI/style impact

- The redesign should express a more intentional Projects screen hierarchy without changing data flow.
- Tailwind utility classes and semantic tokens from issue #61 remain the styling foundation.
- The screen should be responsive from desktop down to narrow mobile widths.
- Focus, hover, disabled, and destructive states must remain visible and keyboard-accessible.

## Verification plan

- `npm run typecheck` in `frontend/`
- `npm run lint` in `frontend/`
- `npm run build` in `frontend/`
- Manual browser smoke test on `/app/projects`
- Manual navigation smoke to `/app/projects/new` and `/app/projects/[projectId]`
- Manual archive/delete confirmation smoke on a populated projects list
- Manual empty-state and error-state smoke, using the existing backend conditions or a temporary failure path if needed
- Desktop and mobile breakpoint checks for overflow, wrapping, and control accessibility

## Risks / blockers

- The Stitch reference is available as analysis metadata, but the local export is not present, so exact spacing and hierarchy still need to be finalized against the reference during implementation.
- The current screen already has destructive actions and inline confirmation; the redesign must not weaken loading/disabled guards.
- A more compact or more decorative reference could tempt shared-primitive changes that are too broad for this issue.
- Long project names, icon keys, and confirmation content may create responsive overflow if the new hierarchy is too dense.
- Empty and error states may be harder to smoke-test than the ready state if the current workspace data is not easy to control locally.

## First recommended implementation slice

Rebuild the `ProjectsListPage` ready-state composition first, using the Stitch reference to reshape the header/list/item hierarchy while leaving the use cases, routes, and confirmation behavior untouched. That gives the largest visual delta with the least behavioral risk and keeps any shared-primitive changes optional until a real gap appears.

## Skills to use

- `frontend-task-implementation`
- `frontend-ui-composition`
- `frontend-reviewer`
- `frontend-style-system`

## Subagents to use

- `frontend-ui-composition` for page composition, responsive layout, and accessibility checks.
- `frontend-reviewer` for a final boundary review once the redesign is implemented.

## Implementation checklist

- [ ] Translate the Stitch Projects List reference into a Boardly-specific layout map for the projects screen.
- [ ] Recompose `ProjectsListPage` to match the reference hierarchy while preserving current project behavior.
- [ ] Restyle loading, empty, error, and ready states without changing their data flow or copy semantics.
- [ ] Preserve create/detail navigation and archive/delete confirmation behavior, including loading guards and inline action errors.
- [ ] Make only minimal generic shared-primitive adjustments if they are required by more than the Projects screen.
- [ ] Verify responsive behavior, keyboard accessibility, and manual smoke coverage for the redesigned `/app/projects` flow.

## Validation checklist

- [ ] `npm run typecheck` in `frontend/`
- [ ] `npm run lint` in `frontend/`
- [ ] `npm run build` in `frontend/`
- [ ] Manual smoke: `/app/projects`
- [ ] Manual smoke: create navigation to `/app/projects/new`
- [ ] Manual smoke: detail navigation to `/app/projects/[projectId]`
- [ ] Manual smoke: archive/delete confirmation, cancel, submit, and inline error handling
- [ ] Manual smoke: loading, empty, and error states
- [ ] Manual smoke: desktop and mobile breakpoint checks

## Next artifact

Expected next artifact: `docs/tasks/issues-64/frontend-implementation.md`
