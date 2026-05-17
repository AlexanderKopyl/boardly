# Frontend Implementation: Issue 64 - Redesign Projects List Using Stitch Reference

## 2026-05-17 - Geometry correction pass: tighten desktop shell offsets and soften row chrome

### Subagents used

- `frontend-ui-composition` for a focused geometry review of the shell and projects list surfaces.

### Skills used

- `frontend-task-implementation`
- `verification-evidence`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Files changed

- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `docs/tasks/issues-64/frontend-verification.md`
- `docs/tasks/issues-64/frontend-implementation.md`

### Summary

- Fixed the desktop workspace shell offset to use a 280px sidebar and matching topbar/content alignment.
- Removed the `WORKSPACE` eyebrow and softened the remaining border treatments on the list page surfaces.
- Tightened the project row geometry toward the target compact card height and icon tile sizing.
- Kept behavior unchanged: no backend, API, auth, or dependency edits.

### Verification

- `npm run typecheck` in `frontend/` passed.
- `npm run lint` in `frontend/` passed.
- `npm run build` in `frontend/` passed.
- Live browser evidence remains partially verified because the available Chrome screenshot still reflects the pre-refresh geometry in this sandbox.

### Risks / follow-up

- The live browser comparison still needs a fresh capture after the shell refresh to confirm the updated geometry.
- No behavior changed outside the allowed frontend files.

## 2026-05-17 - Visual polish pass: soften the shell tokens and constrain the projects surface

### Subagents used

- `frontend-ui-composition` for a focused shell/polish review before the final token pass.

### Skills used

- `frontend-task-implementation`
- `verification-evidence`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-verification.md`
- `docs/design/stitch/projects-list/screen.png`

### Files changed

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-implementation.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Summary

- Softened the workspace shell and projects screen tokens to remove the wireframe feel.
- Constrained the projects content width on large desktop layouts.
- Made the sidebar, topbar, filter bar, rows, empty/error surfaces, and stats/tip cards read closer to the Stitch reference without changing behavior.
- Forced the quick stats card and primary CTA back onto the navy token so the blue anchor is visually obvious again.

### Verification

- `npm run typecheck` in `frontend/` passed.
- `npm run lint` in `frontend/` passed.
- `npm run build` in `frontend/` passed.
- Manual Chrome smoke on `/app/projects` in desktop, iPhone SE, and iPad Mini responsive modes passed without visible overflow or layout breakage.

### Risks / follow-up

- The screen now matches the reference much more closely, but the empty-state variant was not separately loaded during the smoke pass.
- No backend, API, auth, or dependency behavior changed.

## 2026-05-17 - Correction pass: full-screen Stitch-style shell and Projects List runtime rewrite

### Subagents used

- `frontend-ui-composition` for a narrow shell/page composition sanity check before the runtime rewrite.

### Skills used

- `frontend-task-implementation`
- `verification-evidence`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-verification.md`
- `docs/design/stitch/projects-list/DESIGN.md`
- `docs/design/stitch/projects-list/code.html`
- `docs/design/stitch/projects-list/screen.png`

### Files changed

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `frontend/src/app/app/ProtectedWorkspaceShell.tsx`
- `frontend/src/shared/ui/AppShell.tsx`
- `frontend/src/shared/ui/SidebarNav.tsx`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-implementation.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Summary

- Reworked the protected workspace shell so the visible app screen now includes the fixed navy sidebar, Boardly brand block, active Projects nav item, Workspace Settings nav item, bottom New Project CTA, and a top utility bar with breadcrumb/search/avatar chrome.
- Rebuilt the Projects List page into a denser Stitch-style surface with a stronger page header, count badge, filter/count bar, compact bordered row cards, status pills, and lower summary/tip cards.
- Kept all flows on the existing data contract: `id`, `name`, `iconKey`, `status`, and `createdAt`.
- Preserved create/detail navigation plus row-local archive/delete confirmation, cancel, and submit behavior.

### Verification

- `npm run typecheck` in `frontend/` passed.
- `npm run lint` in `frontend/` passed.
- `npm run build` in `frontend/` passed.
- Live browser smoke in Chrome against the production server on `http://localhost:3001/app/projects` passed for create navigation, detail navigation, archive confirmation/cancel, delete confirmation/cancel, and delete submit.

### Risks / follow-up

- The projects row icon tile still uses an abbreviation derived from `iconKey` rather than a true pictographic icon.
- Responsive mobile/tablet smoke was not re-run after the final correction pass, so that remains an open verification item.

## 2026-05-17 - Correction pass: tighten the runtime Projects List page to the Stitch reference

### Subagents used

- `frontend-ui-composition` for a narrow composition check against the current page slice.

### Skills used

- `frontend-task-implementation`
- `verification-evidence`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-verification.md`
- `docs/design/stitch/projects-list/DESIGN.md`
- `docs/design/stitch/projects-list/code.html`

### Files changed

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-implementation.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Summary

- Reworked the Projects List page into a denser Stitch-style composition with a stronger title hierarchy, a right-aligned create action, a ready-state count surface, bordered white project rows, and matched row-level confirmation/error behavior.
- Restyled the loading, empty, and error states to reuse the same visual language as the list cards instead of the previous minimal treatment.
- Kept the implementation scoped to the page file and existing project list fields only.

### Verification

- `npm run typecheck` in `frontend/` passed.
- `npm run lint` in `frontend/` passed.
- `npm run build` in `frontend/` passed.
- `git diff --check` passed with `/opt/homebrew/bin/git`.
- Live browser smoke on `/app/projects` could not be completed in this sandbox because the exposed browser-control surface was not sufficient and GUI screen capture failed from the available display context.

### Risks / follow-up

- The runtime code is in place, but the visual match still needs an actual browser pass against `docs/design/stitch/projects-list/screen.png` before the checklist can be safely marked complete.
- Archive/delete pending and inline error flows remain in the page code, but the live manual smoke is still blocked in this environment.

## 2026-05-17 - Task: Implement the Projects List redesign in `ProjectsListPage`

### Subagents used

- `frontend-ui-composition` to sanity-check the page composition against the approved slice.

### Skills used

- `frontend-task-implementation`
- `verification-evidence`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-planning.md`
- `docs/tasks/issues-64/frontend-analysis.md`
- `docs/tasks/issues-64/onboarding.md`
- `docs/tasks/issues-64/context-pack.md`
- `docs/tasks/issues-64/context-budget.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

### Files changed

- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`
- `docs/tasks/issues-64/frontend-checklist.md`
- `docs/tasks/issues-64/frontend-implementation.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Summary

- Rebuilt the projects ready state as a dense responsive list using only the existing list fields.
- Preserved create navigation, detail navigation, archive/delete confirmations, pending disabled state, and inline action error wiring.
- Kept loading, empty, and error states truthful while matching the new row geometry with a custom skeleton.
- Kept the scope isolated to `ProjectsListPage` and did not touch backend, auth/session, routes, or unrelated screens.

### Verification

- `npm run typecheck` in `frontend/`
- `npm run lint` in `frontend/`
- `npm run build` in `frontend/`
- Manual browser smoke on `/app/projects` for create navigation, detail navigation, archive/delete confirmation flows, and responsive widths

### Risks / follow-up

- Inline action error behavior was exercised under offline network conditions, but the expected visible inline error did not surface during the smoke window.
- The route and shell behaved normally during the change; no backend or session changes were required.

## 2026-05-17 - Task: Translate the Stitch Projects List reference into a Boardly-specific layout map for the projects screen

### Subagents used

- `frontend-ui-composition` requested to validate the layout map framing against the current screen composition.

### Skills used

- `frontend-task-implementation`
- `frontend-ui-composition`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-planning.md`
- `docs/tasks/issues-64/analysis.md`
- `docs/tasks/issues-64/onboarding.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

### Files changed

- `docs/design/stitch/projects-list/README.md`
- `docs/tasks/issues-64/frontend-implementation.md`
- `docs/tasks/issues-64/frontend-checklist.md`

### Summary

- Recorded the Stitch MCP retrieval status for the `Projects List` reference.
- Documented the Boardly-specific layout map for the redesigned `/app/projects` screen.
- Kept the note focused on screen hierarchy, row chrome, state surfaces, and responsive behavior.
- Did not change the runtime screen yet; this slice is the design mapping artifact only.

### Verification

- Verified the Stitch screen fetch via `mcp__stitch__.get_screen` for project `6711793596249838566` and screen `db4fc82ad077451daab0feabc1138197`.
- Verified the repository could not download the signed hosted Stitch URLs from this sandbox, so the README records the retrieval status instead of local asset files.

### Risks / follow-up

- The next implementation slice still needs to translate the layout map into `ProjectsListPage` composition.
- Because the HTML and screenshot could not be downloaded locally, the next slice still has to rely on the Stitch metadata and the current Boardly screen structure rather than a local asset preview.

## 2026-05-17 - Task: Retry Stitch project screen retrieval and record the full screen inventory

### Subagents used

- None.

### Skills used

- `frontend-task-implementation`

### Guidance loaded

- `AGENTS.md`
- `docs/tasks/issues-64/frontend-planning.md`
- `docs/tasks/issues-64/analysis.md`
- `docs/tasks/issues-64/onboarding.md`

### Files changed

- `docs/design/stitch/projects-list/README.md`
- `docs/tasks/issues-64/frontend-verification.md`

### Summary

- Retried Stitch retrieval through the MCP server outside the shell sandbox path.
- Confirmed the full project screen inventory for project `6711793596249838566`.
- Recorded the screen list in the Stitch README so the redesign work has a durable reference inventory.

### Verification

- Verified `mcp__stitch__.list_screens` returned 10 screens for the project.

### Risks / follow-up

- The Stitch screen exports still rely on hosted download URLs rather than local committed assets.
- The next implementation slice can use the recorded inventory as context, but the actual projects list rewrite is still pending.
