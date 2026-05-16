---
name: task-analysis
description: "Analyze a Boardly task before implementation and save analysis artifacts into the specified task folder."
---

# Task Analysis Skill

Use this skill after planning and before implementation, or when the user asks to analyze a task, risk, architecture option, branch, feature, or bug.

This skill produces a durable analysis artifact. Do not only answer in chat when a task folder is specified.

## Required input

- Task goal or planning artifact.
- Task folder path where artifacts must be saved.
- Existing plan path, usually `<task-folder>/planning.md`, if available.
- Target area: backend, frontend, infrastructure, docs, or mixed.

If the task folder is not specified, ask for it before creating artifacts unless the user explicitly asks for chat-only analysis.

## Artifact rule

Save the analysis artifact into the specified task folder.

Default artifact:

```text
<task-folder>/analysis.md
```

If the folder does not exist and file writes are allowed, create it.

## Workflow

1. Read the planning artifact if present.
2. Inspect relevant code/docs without reading sensitive files.
3. Check previous context via MemPalace only if it can materially change the decision.
4. Identify architecture impact.
5. Identify domain/application/infrastructure/UI impact.
6. Identify permission, security, audit, and source-of-truth risks.
7. Identify async/search/cache/frontend implications.
8. Identify tests and verification steps.
9. Update or refine the implementation checklist if needed.
10. Save analysis to `<task-folder>/analysis.md`.

## Artifact format

```markdown
# Analysis: <task title>

## Inputs reviewed

## Current state

## Architecture impact

## Domain/Application/Infrastructure/UI impact

## Frontend impact

## Security / permissions / audit impact

## Async / search / cache impact

## Risks and mitigations

## Test strategy

## Implementation notes

## Refined checklist

- [ ] ...

## Next artifact

Expected next artifact: `<task-folder>/implementation.md` or `<task-folder>/checklist.md`
```

## Boardly rules

- Analysis does not implement code.
- Do not use MemPalace for simple repo discovery.
- Do not read `.env`, SQL dumps, private keys, local DB files, or secrets.
- For backend work, preserve DDD/Hexagonal boundaries and DB source of truth.
- For frontend work, preserve ADR-0006 boundaries and auth/session rules.
