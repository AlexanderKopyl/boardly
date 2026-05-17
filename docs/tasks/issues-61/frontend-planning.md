# Frontend Planning: Tailwind CSS setup and baseline styling migration for issue #61

## Goal

Set up Tailwind CSS in the frontend workspace, establish the semantic CSS token baseline required by ADR-0007, introduce a reusable `cn()` helper, rebuild the shared `Button` and `Input` primitives in a shadcn-style way, and migrate the existing frontend baseline styling where it is currently relying on global bespoke CSS.

## Scope

- Tailwind CSS wiring for the `frontend/` app.
- Semantic CSS variable baseline and global style cleanup.
- Shared `cn()` helper suitable for class composition in primitives.
- Shared `Button` and `Input` primitives.
- Baseline migration of current auth/app shell/dashboard/project screens to the new styling foundation where they already exist.

## Non-goals

- Implementing new Projects business functionality.
- Changing auth/session behavior or token handling.
- Changing backend APIs, permissions, workflow rules, or persistence.
- Creating new future contexts.
- Broad redesign work beyond the current frontend baseline.
- Migrating unrelated product UI that is not part of the existing baseline.

## Guidance loaded

- [x] `AGENTS.md`
- [x] `.codex/agents/instructions/_skill-usage.md`
- [x] `docs/development/frontend/boardly-frontend-developer-rules.md`
- [x] `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- [x] `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md`
- [x] `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- [x] `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`

## Frontend context ownership

- No new frontend context is justified for this task.
- Styling foundation work belongs in `shared` and `app`.
- Existing `identity-access` and `projects` presentation components are consumers of the new styling foundation, not owners of it.
- Any screen-level migration stays inside the current context or route already owning the screen.

## Layer/file placement

| Layer | Responsibility | Planned files |
| --- | --- | --- |
| `app` | Tailwind entrypoint, global token/base styles, root composition | `frontend/postcss.config.mjs`, `frontend/tailwind.config.ts`, `frontend/src/app/globals.css`, possibly `frontend/src/app/layout.tsx` if the global style import needs adjustment |
| `shared` | Generic styling helper and primitives | `frontend/src/shared/lib/cn.ts`, `frontend/src/shared/ui/Button.tsx`, `frontend/src/shared/ui/Input.tsx`, and any minimal supporting shared primitive updates needed to keep the baseline coherent |
| `contexts/identity-access` | Consume the shared styling foundation in auth/session-loading UI | `frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx`, `frontend/src/contexts/identity-access/presentation/ui/RegisterForm.tsx`, `frontend/src/contexts/identity-access/presentation/ui/SessionLoadingState.tsx`, `frontend/src/contexts/identity-access/presentation/ui/LogoutButton.tsx`, `frontend/src/contexts/identity-access/presentation/ui/SidebarAccountCard.tsx` only if they depend on the new shared primitives or tokens |
| `contexts/projects` | Consume the shared styling foundation in existing projects screens | `frontend/src/contexts/projects/presentation/ui/ProjectsListPage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectCreatePage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectDetailsPage.tsx`, `frontend/src/contexts/projects/presentation/ui/ProjectCreateForm.tsx` only as baseline consumers |

## API/auth/session impact

- None expected.
- This task must not modify refresh cookie handling, access token storage, protected-route logic, or auth state transitions.
- Any auth screen styling changes must remain presentation-only.

## UI/style impact

- Tailwind becomes the primary styling system.
- CSS variables remain the source of truth for theme tokens.
- Global CSS should shrink to token definitions, Tailwind layers/base rules, and only the true app-wide layout styles that do not belong in a primitive.
- `Button` and `Input` should move from bespoke global `.ui-*` class coupling to component-owned Tailwind class composition and semantic variants.
- Existing baseline screens should consume the shared primitives instead of carrying their own one-off styling systems.

## Subagents to use

- `frontend-ui-composition` for screen-level composition checks when migrating baseline pages.
- `frontend-reviewer` for a final architecture/style boundary review after the plan is implemented.

## Skills to use

- `frontend-task-implementation`
- `frontend-reviewer`
- `frontend-ui-composition`
- `frontend-style-system`

## Implementation order

1. Wire the Tailwind/PostCSS toolchain in `frontend/` so the repo can build utility classes and token-backed styles.
2. Reduce `frontend/src/app/globals.css` to semantic variables, base/reset rules, and only global layout rules that cannot live elsewhere.
3. Replace the current minimal `cn()` helper with a class-composition helper suitable for shared primitives.
4. Rebuild `Button` and `Input` as shared, token-driven primitives with clear variants and states.
5. Migrate the existing baseline screens that already use the current shared primitives or bespoke global CSS, starting with the most visible shell/auth surfaces and then the current projects screens.
6. Run the narrowest useful verification, then broader frontend checks.

## Implementation checklist

- [ ] Wire the Tailwind/PostCSS toolchain in `frontend/` and confirm the app entrypoint can consume Tailwind layers.
- [ ] Simplify `frontend/src/app/globals.css` to tokens, base styles, and genuinely global layout rules.
- [ ] Replace the current `cn()` helper with a reusable class composition helper for primitives.
- [ ] Rebuild `frontend/src/shared/ui/Button.tsx` and `frontend/src/shared/ui/Input.tsx` as shadcn-style shared primitives.
- [ ] Migrate existing baseline auth/app-shell/project screens to use the shared primitives and token-based styling.
- [ ] Verify the result with build/lint/typecheck and a manual browser smoke test of the affected baseline screens.

## Validation checklist

- [ ] `frontend` build
- [ ] `frontend` lint
- [ ] `frontend` typecheck
- [ ] Manual browser smoke test of login/register/session-loading, protected shell, and current projects screens

## Risks

- Tailwind is not yet installed, so the first slice needs to establish the actual pipeline before any utility-based styling can be trusted.
- `frontend/src/app/globals.css` currently owns a lot of component styling, so cutting it down must be phased to avoid breaking the existing baseline all at once.
- Shared primitives are used across several visible screens, so the primitive contract must stay stable while its internals change.
- There is no frontend test runner in place yet, so verification depends on build/lint/typecheck plus manual browser checks.

## Open questions

- Whether any of the currently bespoke shared UI components beyond `Button` and `Input` need immediate migration, or whether they can remain as temporary consumers of the token baseline for this issue.
- Whether the Tailwind setup should keep the existing `tailwind.config.ts` shape and fill in the missing runtime wiring, or be simplified further during implementation.

## Next artifact

Expected next artifact: `docs/tasks/issues-61/frontend-checklist.md`
