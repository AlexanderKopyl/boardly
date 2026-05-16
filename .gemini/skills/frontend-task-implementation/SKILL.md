---
name: frontend-task-implementation
description: "Implement a Boardly frontend task from a checkbox plan task-by-task, using relevant frontend subagents, ADR-0006/0007, developer rules, and updating frontend checklist/implementation artifacts."
---

# Frontend Task Implementation Skill

Use this skill when the user asks to implement a planned Boardly frontend task, execute a frontend checklist, or continue frontend implementation from a task folder.

## Required sub-skill rule

REQUIRED SUB-SKILL: Use subagent-driven-development for implementation.

Meaning:

- Do not implement everything in one pass.
- Break the frontend plan into checkbox tasks.
- For each checkbox task, select the relevant frontend/backend subagent(s).
- Implement one task at a time.
- Verify that task.
- After verification, update the checkbox from `- [ ]` to `- [x]`.
- Record what changed and how it was verified.

If external `superpowers:subagent-driven-development` is available in the environment, use it. Otherwise follow the local Boardly subagent-driven implementation process defined in this skill.

## Required input

- Task folder path.
- Existing frontend plan/checklist artifact, if available.
- Permission to modify files.

Preferred artifacts:

```text
<task-folder>/frontend-planning.md
<task-folder>/frontend-analysis.md
<task-folder>/frontend-checklist.md
<task-folder>/frontend-implementation.md
<task-folder>/frontend-verification.md
```

## Source of truth

Before implementation and before each material UI/API/auth change, follow relevant guidance:

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` when auth/session is involved
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` when UI/styling/shared primitives are involved
- `docs/development/backend/boardly-symfony-developer-rules.md` when API/auth/backend contract behavior matters

## Artifact rule

Implementation must update the frontend checklist.

If `<task-folder>/frontend-checklist.md` exists, update it.

If it does not exist, create it from the implementation checklist in this order:

1. `<task-folder>/frontend-planning.md`
2. `<task-folder>/frontend-analysis.md`
3. current implementation understanding

Default artifacts:

```text
<task-folder>/frontend-checklist.md
<task-folder>/frontend-implementation.md
<task-folder>/frontend-verification.md
```

## Workflow

1. Read `<task-folder>/frontend-planning.md` if present.
2. Read `<task-folder>/frontend-analysis.md` if present.
3. Read or create `<task-folder>/frontend-checklist.md`.
4. Pick the first unchecked task.
5. Select required subagent(s) for that task.
6. Select required frontend/general skill(s) for that task.
7. Load relevant ADRs and rulebooks for that task.
8. Implement only that task.
9. Run or document relevant verification.
10. Append verification evidence to `<task-folder>/frontend-verification.md`.
11. Update checklist checkbox to `- [x]` only if done and verified/documented.
12. Append implementation notes to `<task-folder>/frontend-implementation.md`.
13. Continue with the next unchecked task only if still within the requested scope.

## Subagent selection examples

- Frontend context/layer task: `frontend-context-architect`.
- Frontend auth/session task: `frontend-identity-access`.
- Frontend UI/component/form/guard task: `frontend-ui-composition`.
- Frontend review task: `frontend-reviewer`.
- API/backend contract task: `symfony-architecture`, `testing-security-reviewer`, or backend-specific agent as needed.
- Verification task: `task-verifier`.

## Skill selection examples

- Context/layer placement: `frontend-context-architecture`.
- Use case/port flow: `frontend-use-case-flow`.
- HTTP gateway/API mapping: `frontend-api-integration`.
- Auth/session handling: `frontend-auth-session`.
- UI composition: `frontend-ui-composition`.
- Styling/shared primitives: `frontend-style-system`.
- Tests: `testing-strategy`.
- Verification: `verification-evidence`.

## Checklist format

```markdown
# Frontend Implementation Checklist: <task title>

- [ ] Task 1
- [ ] Task 2
- [ ] Task 3
```

When completed:

```markdown
- [x] Task 1
```

## Implementation notes format

Append per task:

```markdown
## <date/time> - Task: <task title>

### Subagents used

### Skills used

### Guidance loaded

### Files changed

### Summary

### Verification

### Risks / follow-up
```

## Verification examples

Use commands that fit the actual frontend package setup:

```bash
npm run typecheck
npm run lint
npm run build
npm test
```

or equivalent `pnpm`/`yarn` commands if the project uses them.

If a command cannot be run, record why in `<task-folder>/frontend-verification.md`.

## Boardly rules

- Do not implement broad UI rewrites unless the checklist explicitly requires it.
- Do not skip ADR-0006 boundaries to finish faster.
- Do not skip ADR-0007 style/token/shared primitive rules for UI work.
- Do not mark a checkbox done if code was not changed or verification was not performed/documented.
- Do not read secrets, SQL dumps, local DB files, private keys, or frontend `.env*` files.
- Access token must remain memory-only.
- Refresh token must remain HttpOnly and unreadable by JavaScript.
- Raw HTTP calls must not be scattered inside components/pages.
- Shared UI primitives must remain generic and context-free.
