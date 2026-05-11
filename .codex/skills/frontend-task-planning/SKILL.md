---
name: frontend-task-planning
description: "Create a frontend implementation plan for Boardly and save planning artifacts into the specified task folder, using ADR-0004/0005/0006/0007 and frontend developer rules."
---

# Frontend Task Planning Skill

Use this skill when planning a Boardly frontend feature, refactor, UI slice, auth flow, API integration, frontend skeleton task, or frontend review follow-up.

This skill produces a durable frontend planning artifact. Do not only answer in chat when a task folder is specified.

## Required input

- Task goal.
- Task folder path where artifacts must be saved.
- Frontend scope constraints, if provided.
- Target context, if known: `identity-access`, `projects`, `issues`, `boards`, etc.

If the task folder is not specified, ask for it before creating artifacts unless the user explicitly asks for chat-only planning.

## Source of truth

Always load relevant guidance before planning:

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` when auth/session is involved
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` when UI/styling/shared primitives are involved

Also load backend rules when the frontend task depends on API contract/auth/backend behavior:

- `docs/development/backend/boardly-symfony-developer-rules.md`

## Artifact rule

Save the planning artifact into the specified task folder.

Default artifact:

```text
<task-folder>/frontend-planning.md
```

If the folder does not exist and file writes are allowed, create it.

## Workflow

1. Confirm task goal and frontend scope.
2. Load relevant ADRs and frontend/backend rulebooks.
3. Identify frontend context ownership.
4. Decide whether a new frontend context is justified.
5. Map expected files to `app`, `shared`, or `contexts/<context>`.
6. Identify application use cases and ports.
7. Identify API gateways and API contract mapping.
8. Identify auth/session impact.
9. Identify UI/style system impact from ADR-0007.
10. Select relevant frontend subagents and skills.
11. Split work into checkbox implementation tasks.
12. Save plan to `<task-folder>/frontend-planning.md`.

## Required checklist syntax

```markdown
- [ ] Task 1
- [ ] Task 2
- [ ] Task 3
```

## Artifact format

```markdown
# Frontend Planning: <task title>

## Goal

## Scope

## Non-goals

## Guidance loaded

- [ ] AGENTS.md
- [ ] ADR-0004
- [ ] ADR-0005, if auth/session
- [ ] ADR-0006
- [ ] ADR-0007, if UI/styling
- [ ] frontend developer rules
- [ ] backend developer rules, if API/backend contract matters

## Frontend context ownership

## Layer/file placement

| Layer | Responsibility | Planned files |
| --- | --- | --- |

## API/auth/session impact

## UI/style impact

## Subagents to use

## Skills to use

## Implementation checklist

- [ ] ...

## Validation checklist

- [ ] pnpm/npm typecheck
- [ ] pnpm/npm lint
- [ ] pnpm/npm build
- [ ] tests/manual smoke, as applicable

## Risks

## Open questions

## Next artifact

Expected next artifact: `<task-folder>/frontend-analysis.md`
```

## Boardly rules

- Follow ADR-0006 for context/layer boundaries.
- Follow ADR-0007 for Tailwind, CSS variables, shared UI primitives, shadcn/Radix usage.
- Do not create empty future contexts.
- Do not put raw HTTP calls in pages/components.
- Do not persist access tokens.
- Frontend planning does not implement code.
