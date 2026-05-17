# Issue 64 Onboarding

## Task contract

- Issue: #64
- Branch: `feature/64-redesign-projects-list-using-stitch-reference-design`
- Mode: analysis only
- Goal: analyze how to redesign `/app/projects` using the Stitch reference while preserving current Projects behavior and Boardly frontend architecture.

## Current repository facts

- `frontend/src/app/app/projects/page.tsx` composes `ProjectsListPage`.
- `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` owns the current projects list screen behavior.
- The projects context already includes list, create, details, archive, and delete use cases plus an HTTP gateway.
- Shared UI primitives already exist for `Button`, `Input`, `Card`, `Badge`, `Alert`, `EmptyState`, `PageHeader`, `Skeleton`, `AppShell`, and `SidebarNav`.
- `frontend/src/app/globals.css` already contains the Tailwind entrypoint and semantic CSS token baseline.
- `frontend/package.json` already includes Tailwind/PostCSS packages, so the issue-61 styling baseline is present on this branch.

## Guidance loaded

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/analysis.md`
- `docs/tasks/issues-61/frontend-planning.md`
- `docs/tasks/issues-61/frontend-verification.md`

## Stitch reference status

- Stitch MCP is available.
- Screen retrieved: `projects/6711793596249838566/screens/db4fc82ad077451daab0feabc1138197`
- Title: `Projects List`
- Device: desktop
- Exportable artifacts reported by MCP:
  - screenshot download URL
  - HTML code download URL
- Detailed pixel-level inspection was not exposed directly in the tool output here, so the reference is currently available as metadata plus external artifact links.

## Artifact location guidance

- Recommended local save path for Stitch outputs: `docs/design/stitch/projects-list/`
- Suggested files if the artifacts are exported locally later:
  - `docs/design/stitch/projects-list/projects-list.screenshot.png`
  - `docs/design/stitch/projects-list/projects-list.html`
  - `docs/design/stitch/projects-list/projects-list.screen.json`
  - `docs/design/stitch/projects-list/README.md`

## Immediate next step

- Write `docs/tasks/issues-64/analysis.md` with the current-state analysis and design implications.
