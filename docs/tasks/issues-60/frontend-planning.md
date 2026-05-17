# Frontend Planning: Issue #60 Projects Slice

## Goal

Deliver the first frontend Projects slice for Boardly:

- projects listing
- project creation
- project details
- archive project from the listing
- delete project from the listing

The slice must fit the current frontend hexagonal structure, reuse the existing auth/session flow, and stay within the backend API as the source of truth.

## Scope

- Add a new `projects` frontend context.
- Add a protected workspace shell for `/app/*` routes so Projects pages can share the same layout without duplicating shell markup.
- Add a Projects listing page as the first usable screen in the slice.
- Add a Projects creation page.
- Add a Projects details page.
- Add archive and delete row actions on the listing with explicit confirmation and refresh after completion.
- Map Projects API transport contracts into frontend domain models and use cases.
- Reuse the existing memory-only auth session and shared HTTP client.

## Non-goals

- Editing or renaming projects.
- Project membership, permissions UI, or role management.
- Issues, boards, workflow, search, or reporting inside a project.
- New auth/session behavior.
- New design system work beyond current shared primitives and page composition.
- Backend changes.

## Guidance loaded

- [x] `AGENTS.md`
- [x] `.codex/config.toml`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/development/backend/boardly-symfony-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [x] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- [x] `docs/tasks/issues-60/onboarding.md`
- [x] `docs/tasks/issues-60/analysis.md`

## Frontend context ownership

- `projects` will own all Projects-specific domain types, gateway contracts, use cases, and presentation components.
- `identity-access` stays responsible for auth session state only.
- `shared` remains generic and must not learn Projects-specific behavior.
- `app` owns routing, protected shell composition, and page-level assembly.

## Layer/file placement

| Layer | Responsibility | Planned files |
| --- | --- | --- |
| `app` | Protected shell, routing, and page composition | `frontend/src/app/app/layout.tsx`, `frontend/src/app/app/projects/page.tsx`, `frontend/src/app/app/projects/new/page.tsx`, `frontend/src/app/app/projects/[projectId]/page.tsx`, `frontend/src/app/app/dashboard/page.tsx` |
| `contexts/projects/domain` | Frontend project types and result/error models | `frontend/src/contexts/projects/domain/project.ts`, `frontend/src/contexts/projects/domain/project-status.ts`, `frontend/src/contexts/projects/domain/project-summary.ts`, `frontend/src/contexts/projects/domain/project-form.ts`, `frontend/src/contexts/projects/domain/project-errors.ts` |
| `contexts/projects/application` | Use cases and ports | `frontend/src/contexts/projects/application/ports/project-gateway.ts`, `frontend/src/contexts/projects/application/use-cases/list-projects.ts`, `frontend/src/contexts/projects/application/use-cases/create-project.ts`, `frontend/src/contexts/projects/application/use-cases/get-project.ts`, `frontend/src/contexts/projects/application/use-cases/archive-project.ts`, `frontend/src/contexts/projects/application/use-cases/delete-project.ts` |
| `contexts/projects/infrastructure` | API transport contracts and gateway implementation | `frontend/src/contexts/projects/infrastructure/http/projects-api-contracts.ts`, `frontend/src/contexts/projects/infrastructure/http/projects-http-gateway.ts` |
| `contexts/projects/presentation` | Screen composition, forms, list row actions, loading and empty states | `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectRowActions.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectCreateForm.tsx`, `frontend/src/contexts/projects/presentation/hooks/useProjectsPage.ts` |
| `shared` | Only if a genuinely reusable primitive is needed for confirmation or layout | Optional `frontend/src/shared/ui/*` additions only if the slice cannot stay within existing primitives |

## API/auth/session impact

- The Projects gateway should read the current access token from the existing memory-only auth session path, not from browser storage.
- Requests should continue using the shared HTTP client so refresh-on-401 behavior stays centralized.
- Archive and delete can rely on `204 No Content`; list/detail/create should map the current backend response DTOs into frontend domain types.
- API errors should be normalized at the Projects infrastructure boundary so presentation code sees typed, screen-usable failures.
- No new auth/session endpoint work is needed for this slice.

## UI/style impact

- Reuse the current protected shell, `PageHeader`, `Card`, `Badge`, `Button`, `EmptyState`, `Skeleton`, `Input`, and related shared primitives.
- Keep Projects UI consistent with the existing app shell and current CSS-variable-based styling.
- Add an explicit confirmation UI for archive/delete so the listing actions are intentional and reversible before the request is sent.
- Avoid introducing a new broad styling system or a new design system layer for this slice.

## Subagents to use

- `frontend-context-architect` for context and layer boundary validation.
- `frontend-use-case-flow` for Projects use-case orchestration and port design.
- `frontend-api-integration` for gateway and transport contract mapping.
- `frontend-ui-composition` for route and page composition decisions.
- `frontend-review-checklist` before merge to catch boundary, auth, and UI regressions.

## Skills to use

- `frontend-context-architecture`
- `frontend-use-case-flow`
- `frontend-api-integration`
- `frontend-ui-composition`
- `frontend-review-checklist`
- `frontend-style-system` if confirmation UI or shell composition needs a reusable primitive decision

## Implementation order

1. Extract the protected `/app` workspace shell so `Projects`, `Dashboard`, and future protected pages can share one layout.
2. Create the `projects` context domain, ports, use cases, and API gateway contracts.
3. Wire the Projects listing page first, because it validates the route, shell, auth session, and read-model mapping in one pass.
4. Add the create page and create form flow.
5. Add the detail page and detail data load flow.
6. Add archive and delete row actions with confirmation, mutation handling, and list refresh.
7. Update sidebar navigation and any placeholder dashboard route behavior so Projects is reachable without dead links.
8. Verify with frontend typecheck, lint, build, and an authenticated manual smoke pass.

## Implementation checklist

- [ ] Extract the protected `/app` workspace shell into a shared layout for all protected pages.
- [ ] Create the `projects` frontend context with domain, application, and infrastructure boundaries.
- [ ] Implement the Projects listing use case, gateway mapping, and `/app/projects` page.
- [ ] Implement the project creation use case, form, and `/app/projects/new` page.
- [ ] Implement the project details use case and `/app/projects/[projectId]` page.
- [ ] Implement archive and delete listing actions with confirmation, error handling, and refresh.
- [ ] Update protected navigation so Projects is the first usable slice in the app shell.
- [ ] Validate the slice with lint, typecheck, build, and manual authenticated smoke testing.

## Validation checklist

- [ ] `cd frontend && pnpm typecheck`
- [ ] `cd frontend && pnpm lint`
- [ ] `cd frontend && pnpm build`
- [ ] Manual authenticated smoke test for list, create, detail, archive, and delete flows

## Risks

- The current protected workspace shell is embedded in the dashboard page, so extracting it to a layout may touch more files than the Projects pages themselves.
- The frontend has no repo-owned test runner today, so regression coverage may rely mainly on typecheck, lint, build, and manual smoke until tests are added.
- Archive/delete confirmation UX can drift if it is implemented ad hoc instead of as a deliberate reusable pattern.
- The backend Projects contract is small but authoritative; any mismatch in status values or response shapes should be treated as a contract mapping bug, not a UI assumption.
- The existing dashboard route may need a small cleanup so sidebar navigation does not leave dead or duplicate entry points.

## Open questions

- None blocking. This plan treats `/app/projects` as the first protected slice and keeps `Dashboard` as secondary/legacy until explicitly revised.

## First recommended implementation slice

Start with shell extraction plus the Projects read path:

- move the protected workspace shell into `frontend/src/app/app/layout.tsx`
- add the Projects context gateway and list use case
- ship `/app/projects` before create/detail/actions

That gives early validation for route structure, auth/session reuse, and backend API mapping before the mutation flows are added.

## Next artifact

Expected next artifact: `docs/tasks/issues-60/frontend-checklist.md`
