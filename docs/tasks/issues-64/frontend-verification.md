# Frontend Verification: Issue 64 - Redesign Projects List Using Stitch Reference

## 2026-05-17 - Geometry correction pass: tighten shell offsets and row rhythm

### Visual evidence

- Current screen: latest Chrome screenshot at `http://localhost:3000/app/projects`
- Target screen: `docs/design/stitch/projects-list/screen.png`

### CURRENT vs TARGET geometry table

| Area | CURRENT | TARGET |
| --- | --- | --- |
| Sidebar width | Roughly 280px on desktop, but the inner rhythm still reads a little looser than the reference. | Exact 280px desktop sidebar with about 24px internal padding. |
| Topbar x offset | Starts at the sidebar edge, but the fixed header still needs the explicit 280px shell offset. | Starts at x=280px with width `calc(100% - 280px)` or equivalent. |
| Main content x offset | Content begins after the sidebar, but the title/filter column still feels slightly under-tuned. | Content begins after the 280px sidebar with about 32px inner padding. |
| WORKSPACE eyebrow | Present above the Projects title in the latest screenshot. | Removed. |
| Filter bar width/alignment | Aligned to the list column, but the border/spacing still reads a touch heavier than the target. | Shares the same content column as the rows, stats, and tip with softer borders. |
| Row width/alignment | Row spans the content column, but the card density is still a bit too airy. | Same column, compact card row geometry. |
| Row height/density | About 90px+ in the latest screenshot, slightly looser than the target. | Compact desktop row height around 84-90px. |
| Border softness | Several surfaces still read like near-black wireframe outlines. | Soft token-based borders/opacities. |
| Stats/tip alignment | Stats and tip already share the same grid, but the vertical rhythm is not fully reference-tight. | Shared content column with tighter vertical rhythm. |

### Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `npm run typecheck` in `frontend/` | Passed | `tsc --noEmit` completed successfully after the geometry pass. |
| `npm run lint` in `frontend/` | Passed | `eslint .` completed successfully after the geometry pass. |
| `npm run build` in `frontend/` | Passed | `next build` completed successfully and produced the app routes. |

### Manual checks

| Check | Result | Notes |
| --- | --- | --- |
| Browser geometry comparison | Partially verified | The latest browser screenshot was used as CURRENT evidence, but the live page was not refreshed to a new captured screenshot in this sandbox after the code patch. |

### Failures

- The latest available browser screenshot still shows the pre-refresh shell/page geometry, so the final visual correction pass remains partially verified.

### Not run

| Command | Reason |
| --- | --- |
| None | All required frontend commands were run. |

### Final verification status

PARTIALLY_VERIFIED

### Remaining risks

- The code/build checks passed, but the live screenshot comparison still needs a fresh browser refresh/capture to confirm that the 280px sidebar, topbar offset, eyebrow removal, and border softening are all rendered exactly as intended.

## 2026-05-17 - Final visual polish pass verification

### Visual evidence

- Current screen: live Chrome at `http://127.0.0.1:3000/app/projects`
- Target screen: `docs/design/stitch/projects-list/screen.png`

### Visual polish checklist

- [x] Borders no longer read as black/heavy.
- [x] Main content is constrained on wide desktop.
- [x] Sidebar is closer to the Stitch target.
- [x] Topbar is closer to the Stitch target.
- [x] Filter bar is closer to the Stitch target.
- [x] Project row density is closer to the Stitch target.
- [x] Quick stats card reads as the blue Stitch-style block.
- [x] Empty state is no longer wireframe-like.

### Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `npm run typecheck` in `frontend/` | Passed | `tsc --noEmit` completed successfully after the final polish pass. |
| `npm run lint` in `frontend/` | Passed | `eslint .` completed successfully after the final polish pass. |
| `npm run build` in `frontend/` | Passed | `next build` completed successfully and produced the app routes. |

### Manual checks

| Check | Result | Notes |
| --- | --- | --- |
| `/app/projects` desktop rendering | Passed | Verified in Chrome against the local dev server. |
| Mobile viewport smoke | Passed | Verified in Chrome DevTools responsive emulation using `iPhone SE` with no visible horizontal overflow. |
| Tablet viewport smoke | Passed | Verified in Chrome DevTools responsive emulation using `iPad Mini` with no visible horizontal overflow. |
| Sidebar/topbar/filter/row polish | Passed | Borders, density, and width matched the target much more closely than before. |
| Quick stats block | Passed | The stats card reads as the blue/navy anchor again. |

### Remaining visual mismatches

- No major visual mismatches were observed in the smoke pass.
- The empty-state variant was restyled in code but was not separately loaded during the live browser pass.

### Final verification status

VERIFIED

## 2026-05-17 - CURRENT vs TARGET mismatch table from live Chrome and Stitch reference

### Visual evidence

- Current screen: live Chrome at `http://localhost:3000/app/projects`
- Target screen: `docs/design/stitch/projects-list/screen.png`

### Mismatch table

| Area | Current | Target |
| --- | --- | --- |
| Workspace shell | Dark sidebar exists, but the overall shell still reads like a generic app layout and does not match the full Stitch composition. | Fixed navy left sidebar integrated into the full screen composition with a clear workspace shell. |
| Sidebar brand block | Boardly branding is present, but the brand block spacing, hierarchy, and active nav styling are looser than the reference. | Strong brand block with compact spacing, bold title, muted subtitle, and clear active Projects item. |
| Sidebar nav | Projects is active, but Workspace Settings is missing and the bottom CTA/sign-out region does not match the reference structure. | Projects active, Workspace Settings visible, and a pinned bottom New Project button. |
| Topbar | No visible breadcrumb/search/icon strip like the Stitch reference. | Breadcrumb-style topbar with search field and icon/profile cluster. |
| Page header | Title/subtitle/button are present, but the spacing and hierarchy are too minimal and not aligned to the target rhythm. | Large Projects title, muted subtitle, and right-aligned primary New Project action with stronger visual hierarchy. |
| Filter/count bar | Missing entirely. | White bordered filter/count surface directly under the header. |
| Project rows | Only one row is shown and it looks like a generic bordered item rather than a Stitch-style compact card row. | Dense white bordered rows with left icon tile, name, metadata, status badge, and right-side action area. |
| Loading state | Skeletons are not visibly matched to the final row geometry. | Skeleton rows aligned to the final compact row structure. |
| Empty/error states | Generic notice surfaces do not share the same row/list visual system. | Empty and error states use the same visual language as the list. |

### Current correction gate

- The current implementation is still closer to the old minimal layout than to `screen.png`.
- The remaining work must include shell-level and page-level composition changes before any checklist item can be marked done.

## 2026-05-17 - Correction pass verification after runtime rewrite

### Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `npm run typecheck` in `frontend/` | Passed | `tsc --noEmit` completed successfully after the shell/page rewrite. |
| `npm run lint` in `frontend/` | Passed | `eslint .` completed successfully. |
| `npm run build` in `frontend/` | Passed | `next build` completed successfully and produced the app routes. |

### Manual checks

| Check | Result | Notes |
| --- | --- | --- |
| `/app/projects` browser rendering | Passed | Verified in Chrome against the live production server on `http://localhost:3001/app/projects`. |
| Create navigation | Passed | `New Project` in the header navigated to `/app/projects/new`. |
| Detail navigation | Passed | Clicking the row `View Details` action navigated to `/app/projects/[projectId]`. |
| Archive/delete confirmation and cancel | Passed | Archive opened a nested inline confirmation and cancel returned to the row; delete opened the same nested confirmation and submit removed the row. |
| Responsive mobile/tablet/desktop check | Partially verified | Desktop browser smoke was completed; mobile/tablet were not re-run after the final correction pass. |

### Browser evidence

- Manual visual evidence: live Chrome on `http://localhost:3001/app/projects` with the production server started from `frontend/`.
- Supporting route evidence: `/app/projects/new` and `/app/projects/[projectId]` were both exercised from the rewritten list.
- No screenshot file path was captured; the verification was recorded directly from the live browser render.

### Remaining visual mismatches

- The project icon tile still renders as a two-letter glyph derived from `iconKey` instead of a pictographic icon.
- The browser chrome in Chrome still shows the usual extension/toolbar affordances outside the app canvas; they are not part of the Boardly screen itself.

### Unchecked checklist items

- Responsive mobile/tablet/desktop check. The final correction pass was only re-smoked in the desktop browser.

### Rollback commands

- `git restore frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx frontend/src/app/app/ProtectedWorkspaceShell.tsx frontend/src/shared/ui/AppShell.tsx frontend/src/shared/ui/SidebarNav.tsx docs/tasks/issues-64/frontend-verification.md docs/tasks/issues-64/frontend-implementation.md docs/tasks/issues-64/frontend-checklist.md`
- `git restore --source=HEAD~1 -- frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` is not required here; a normal restore is sufficient if you need to back out only the runtime change.

### Final verification status

PARTIALLY_VERIFIED

## 2026-05-17 - Correction pass: tighten the runtime Projects List page to the Stitch reference

### Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `npm run typecheck` in `frontend/` | Passed | `tsc --noEmit` completed successfully. |
| `npm run lint` in `frontend/` | Passed | `eslint .` completed successfully. |
| `npm run build` in `frontend/` | Passed | `next build` completed successfully. |
| `/opt/homebrew/bin/git diff --check` | Passed | No whitespace or patch formatting issues. |

### Manual checks

| Check | Result | Notes |
| --- | --- | --- |
| `/app/projects` browser rendering | Not run | The sandbox did not expose a usable live browser-control/screenshot path for a visual comparison pass. |
| Create navigation | Not run | Could not complete a live browser smoke in this sandbox. |
| Detail navigation | Not run | Could not complete a live browser smoke in this sandbox. |
| Archive/delete confirmation and cancel | Not run | Could not complete a live browser smoke in this sandbox. |
| Responsive mobile/tablet/desktop check | Not run | Could not complete a live browser smoke in this sandbox. |

### Visible mismatch checklist

- [ ] Page title, subtitle, and create action match the Stitch header rhythm.
- [ ] Ready-state rows read as compact white bordered cards instead of a plain list.
- [ ] Left icon tile, project name, created metadata, status badge, and details link/action area match the Stitch row hierarchy closely enough.
- [ ] Loading skeleton rows match the final row geometry.
- [ ] Empty and error states use the same visual system as the list.
- [ ] Archive/delete confirmation still feels nested and row-local rather than dialog-like.

### Failures

- Live browser verification could not be completed in this sandbox, so the visual match remains unconfirmed.

### Not run

| Command | Reason |
| --- | --- |
| `npm run typecheck` | Already run and passed. |
| `npm run lint` | Already run and passed. |
| `npm run build` | Already run and passed. |

### Final verification status

PARTIALLY_VERIFIED

### Remaining risks

- The code compiles and builds, but the live Stitch comparison still needs a browser pass before the checkbox state can be upgraded.
- The desktop/mobile/tablet smoke results are still pending due the missing browser-control surface in this environment.

## 2026-05-17 - Task: Implement the Projects List redesign in `ProjectsListPage`

### Code checks

- `npm run typecheck` in `frontend/` passed.
- `npm run lint` in `frontend/` passed.
- `npm run build` in `frontend/` passed.

### Manual browser smoke

- `/app/projects` loaded the redesigned dense list state in Chrome.
- Create navigation worked from the list page into `/app/projects/new`.
- Project detail navigation worked from the project name link into `/app/projects/[projectId]`.
- Archive confirmation rendered, cancel returned to the list row, and archive submit removed the row in an earlier smoke pass.
- Delete confirmation rendered for a fresh temporary project.
- Delete cancel returned to the list row.
- Delete submit was attempted with Chrome devtools network throttled to `Offline`; the request failed in the network panel with `net::ERR_INTERNET_DISCONNECTED`, and the row buttons became disabled.
- A visible inline action error did not appear during the offline delete smoke window, so that piece is not fully confirmed by the UI screenshot evidence.

### Responsive check

- Mobile: iPhone SE emulation at `375x667` fit the page without visible horizontal overflow.
- Tablet: iPad Mini emulation at `768x1024` fit the page without visible horizontal overflow.
- Desktop: the normal browser viewport rendered the list row and shell correctly at a wide desktop size, with no visible overflow in the projects surface.

## 2026-05-17 - Task: Translate the Stitch Projects List reference into a Boardly-specific layout map for the projects screen

### Verification evidence

- `mcp__stitch__.get_screen` returned the `Projects List` screen for project `6711793596249838566` and screen `db4fc82ad077451daab0feabc1138197`.
- The response included hosted HTML and screenshot download URLs.
- Direct sandbox download to the Google-hosted URLs was blocked, so no local binary artifact was written.
- The reference README was written to `docs/design/stitch/projects-list/README.md` with the retrieval status and layout map.

### Notes

- This was a documentation-only slice, so no frontend runtime command was required yet.

## 2026-05-17 - Task: Retry Stitch project screen retrieval and record the full screen inventory

### Verification evidence

- `mcp__stitch__.list_screens` returned 10 screens for project `6711793596249838566`.
- The returned titles included:
  - `Projects List`
  - `Create Project`
  - `Projects List - Loading Skeleton`
  - `Project Details - Not Found State`
  - `Create Project - Submitting State`
  - `Project Details - Delete Confirmation`
  - `Project Details`
  - `Project Details - Archive Confirmation`
  - `Create Project - Validation Errors`
  - `Projects List - Empty State`
- The screen inventory was documented in `docs/design/stitch/projects-list/README.md`.

### Notes

- No frontend runtime verification was required for this Stitch retrieval step.
