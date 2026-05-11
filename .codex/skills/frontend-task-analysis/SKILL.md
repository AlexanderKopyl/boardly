---
name: frontend-task-analysis
description: "Analyze a Boardly frontend task before implementation and save frontend analysis artifacts into the specified task folder, including ADR/rule impact, API/auth/UI/style risks, and tests."
---

# Frontend Task Analysis Skill

Use this skill after frontend planning and before frontend implementation, or when analyzing frontend architecture, UI, auth, API integration, styling, or test risks.

This skill produces a durable frontend analysis artifact. Do not only answer in chat when a task folder is specified.

## Required input

- Task goal or planning artifact.
- Task folder path where artifacts must be saved.
- Existing frontend plan path, usually `<task-folder>/frontend-planning.md`, if available.

If the task folder is not specified, ask for it before creating artifacts unless the user explicitly asks for chat-only analysis.

## Source of truth

Always load relevant guidance before analysis:

- `AGENTS.md`
- `.codex/agents/instructions/_skill-usage.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` when auth/session is involved
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md` when UI/styling/shared primitives are involved

Load backend rules when API contracts, auth endpoints, cookies, or backend validation matter:

- `docs/development/backend/boardly-symfony-developer-rules.md`

## Artifact rule

Save the analysis artifact into the specified task folder.

Default artifact:

```text
<task-folder>/frontend-analysis.md
```

If the folder does not exist and file writes are allowed, create it.

## Workflow

1. Read `<task-folder>/frontend-planning.md` if present.
2. Inspect relevant frontend files/docs without reading sensitive files.
3. Use MemPalace only if remembered context can materially change the decision.
4. Validate frontend context ownership and ADR-0006 layer boundaries.
5. Validate styling/UI strategy against ADR-0007.
6. Validate API contract mapping and backend rulebook impact.
7. Validate auth/session model if relevant.
8. Identify presentation/application/domain/infrastructure impact.
9. Identify security, accessibility, performance, and test risks.
10. Refine implementation checklist if needed.
11. Save analysis to `<task-folder>/frontend-analysis.md`.

## Artifact format

```markdown
# Frontend Analysis: <task title>

## Inputs reviewed

## Guidance loaded

- [ ] AGENTS.md
- [ ] ADR-0004
- [ ] ADR-0005, if auth/session
- [ ] ADR-0006
- [ ] ADR-0007, if UI/styling
- [ ] frontend developer rules
- [ ] backend developer rules, if API/backend contract matters

## Current state

## Frontend context/layer impact

## API contract impact

## Auth/session impact

## UI/style system impact

## Accessibility impact

## Security/privacy impact

## Performance impact

## Test strategy

## Risks and mitigations

## Refined checklist

- [ ] ...

## Next artifact

Expected next artifact: `<task-folder>/frontend-checklist.md` and `<task-folder>/frontend-implementation.md`
```

## Boardly rules

- Analysis does not implement code.
- Do not use MemPalace for simple repo discovery.
- Do not read `.env`, SQL dumps, private keys, local DB files, or secrets.
- Frontend domain models are not backend aggregates.
- Backend remains source of truth for permissions, workflow, identity lifecycle, persistence, and business invariants.
- Access tokens must remain memory-only.
- Refresh tokens must remain HttpOnly and unreadable by JavaScript.
