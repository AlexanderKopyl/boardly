# Context Budget Audit: Issue 64 - Redesign Projects List Using Stitch Reference

## Task type

Frontend planning/analysis for a Projects List presentation redesign. No implementation in this step.

## Candidate context sources

Approximate tokens use `characters / 4`.

| Source | Approx chars | Approx tokens | Decision | Reason |
| --- | ---: | ---: | --- | --- |
| `AGENTS.md` | 6908 | 1727 | load-full | Required operating, security, architecture, and artifact rules. |
| `.codex/config.toml` | 11512 | 2878 | summarize | Useful for enabled skills/subagents and permission posture; too much agent registration detail for repeated full load. |
| `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` | 16966 | 4242 | load-section | Need decision/rules only; alternatives can be summarized. |
| `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md` | 18611 | 4653 | load-section | Need architecture rules and layer responsibilities. |
| `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` | 14522 | 3631 | load-section | Need decision, token, component, layer rules. |
| `docs/development/frontend/boardly-frontend-developer-rules.md` | 21499 | 5375 | load-section | Need app/shared/context, presentation, API, TypeScript rules; skip examples unless needed. |
| `docs/design/stitch/projects-list/README.md` | 3811 | 953 | load-full | Compact and directly relevant. |
| `docs/design/stitch/projects-list/DESIGN.md` | 8575 | 2144 | load-full | Direct design tokens and component guidance. |
| `docs/design/stitch/projects-list/code.html` | 19103 | 4776 | load-section | Heavy standalone export; load Tailwind config and relevant page/list/state sections only. |
| `docs/design/stitch/projects-list/screen.png` | n/a | n/a | load-on-demand | Use image inspection for visual fidelity during planning, not for every reasoning step. |
| `frontend/package.json` | 624 | 156 | load-full | Confirms stack/scripts/dependencies. |
| `frontend/postcss.config.mjs` | 92 | 23 | load-full | Confirms Tailwind PostCSS plugin. |
| `frontend/tailwind.config.ts` | 1525 | 381 | load-full | Confirms token mapping. |
| `frontend/src/app/globals.css` | 1873 | 468 | load-full | Current CSS variables and base styles. |
| `frontend/src/app/layout.tsx` | small | small | load-full | Root layout/global CSS import. |
| `frontend/src/app/app/layout.tsx` | small | small | load-full | Workspace layout wrapper. |
| `frontend/src/app/app/ProtectedWorkspaceShell.tsx` | 3633 | 908 | load-full | Current app shell/sidebar composition. |
| `frontend/src/app/app/projects/page.tsx` | 165 | 41 | load-full | Thin Projects List route. |
| `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx` | 12340 | 3085 | load-full | Primary future target; state and behavior preservation depends on full read. |
| `frontend/src/shared/lib/cn.ts` | small | small | load-full | Utility used by shared primitives. |
| `frontend/src/shared/ui/Button.tsx` | 2464 | 616 | load-full | Existing action primitive. |
| `frontend/src/shared/ui/Card.tsx` | 438 | 110 | load-full | Existing surface primitive. |
| `frontend/src/shared/ui/Badge.tsx` | 1128 | 282 | load-full | Existing status primitive. |
| `frontend/src/shared/ui/PageHeader.tsx` | 1844 | 461 | load-full | Existing header primitive. |
| `frontend/src/shared/ui/EmptyState.tsx` | 1457 | 364 | load-full | Existing empty/error state primitive. |
| `frontend/src/shared/ui/Skeleton.tsx` | 390 | 98 | load-full | Existing loading primitive. |
| `frontend/src/shared/ui/Alert.tsx` | small | small | load-on-demand | Needed if planning touches inline errors. |
| `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts` | small | small | load-full | Confirms fields available to UI. |
| `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts` | medium | medium | summarize | Need endpoints/mapping only; implementation details are stable. |
| `src/Boardly/Projects/Interfaces/Http/Controller/ProjectController.php` | medium/large | section only | load-section | Need list/create route and bus usage only if API contract questions arise. |
| `templates/base.html.twig` | small | small | summarize | Confirms Twig is not product UI; do not dwell. |
| Existing `docs/tasks/issues-64/frontend-planning.md` and `analysis.md` | medium | medium | summarize | Useful for stale-plan reconciliation, but not source of current truth. |

## Heavy sources

- Frontend developer rules: about 5.4k estimated tokens.
- ADR-0004/0006/0007 together: about 12.5k estimated tokens.
- Stitch `code.html`: about 4.8k estimated tokens and contains standalone CDN/font/image details that should not be copied directly.
- `ProjectsListPage.tsx`: about 3.1k estimated tokens, but it is the primary behavioral source and should be loaded fully.
- `.codex/config.toml`: about 2.9k estimated tokens; summarize after initial orientation.

## Stable prefix candidates

- `AGENTS.md`
- ADR-0004 rules/decision summary
- ADR-0006 layer rules summary
- ADR-0007 styling/shared primitive rules summary
- Frontend developer rules summary
- Current task contract

Keep these stable in planning/analysis prompts for better reuse and to avoid re-litigating architecture constraints.

## Variable evidence

- Current `ProjectsListPage.tsx` structure and line-level behavior.
- Stitch `code.html` specific page/list/row/state sections.
- `DESIGN.md` tokens and component guidance.
- Shared primitive implementation details if planning proposes primitive changes.
- Backend controller/DTO sections only if new data fields or API behavior are proposed.

## Recommended loading order

1. Stable guidance summaries: `AGENTS.md`, ADR-0004, ADR-0006, ADR-0007, frontend rules.
2. Task artifacts: `onboarding.md`, `context-pack.md`, this `context-budget.md`.
3. Design sources: `README.md`, `DESIGN.md`, selected `code.html` sections, optional `screen.png`.
4. Frontend stack/config: package, PostCSS, Tailwind config, global CSS.
5. Route/shell: app layouts and `ProtectedWorkspaceShell`.
6. Primary component: `ProjectsListPage.tsx`.
7. Shared primitives needed by the plan.
8. API contracts/gateway/backend sections only when data-contract risk appears.

## Compaction recommendation

No compaction is needed now. For the next planning/analysis step, aim for roughly 6k-10k useful tokens by loading summarized ADR/rule guidance, full design `README.md`/`DESIGN.md`, targeted `code.html` sections, full `ProjectsListPage.tsx`, and the small shared primitive files. Avoid carrying raw command outputs forward.

## Skipped sources

- `vendor/**`, `node_modules/**`, `frontend/node_modules/**`, `var/cache/**`, generated/build artifacts.
- Secrets and local environment files.
- Full backend project implementation outside the Projects API route/contract.
- Full previous issue artifacts except as summaries, because existing issue-64 analysis/planning contains stale statements that local Stitch artifacts were unavailable.
- Full `code.html` in repeated prompts; use targeted sections and summaries instead.

## Risks

- Overloading context with full ADRs plus full frontend rules can crowd out the actual screen/design evidence.
- Copying Stitch standalone HTML too literally would introduce external fonts/icons/images or fake filter/search behavior not backed by Boardly contracts.
- The current backend list contract does not include owner/search/stat fields shown in the Stitch reference; planning must not assume those fields exist.
- Existing `Card` radius and screen density differ from `DESIGN.md`; any shared primitive change should be justified as generic, not project-specific.
- Existing issue-64 planning/analysis artifacts are partially stale and should be updated or superseded before implementation.

## No implementation note

No application code was edited. This audit is documentation-only.
