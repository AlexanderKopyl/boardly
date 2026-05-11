---
name: task-implementation
description: "Implement a Boardly task from a checkbox plan task-by-task, using relevant subagents per task and updating or creating the checklist artifact."
---

# Task Implementation Skill

Use this skill when the user asks to implement a planned Boardly task, execute a checklist, or continue implementation from a task folder.

## Required sub-skill rule

REQUIRED SUB-SKILL: Use subagent-driven-development for implementation.

Meaning:

- Do not implement everything in one pass.
- Break the plan into checkbox tasks.
- For each checkbox task, select the relevant subagent(s).
- Implement one task at a time.
- After finishing a task, update the checkbox from `- [ ]` to `- [x]`.
- Record what changed and how it was verified.

If external `superpowers:subagent-driven-development` is available in the environment, use it. Otherwise follow the local Boardly subagent-driven implementation process defined in this skill.

## Required input

- Task folder path.
- Existing plan/checklist artifact, if available.
- Permission to modify files.

Preferred artifacts:

```text
<task-folder>/planning.md
<task-folder>/analysis.md
<task-folder>/implementation.md
<task-folder>/checklist.md
```

## Artifact rule

Implementation must update the checklist.

If `<task-folder>/checklist.md` exists, update it.

If it does not exist, create it from the implementation checklist in this order:

1. `<task-folder>/planning.md`
2. `<task-folder>/analysis.md`
3. current implementation understanding

Default artifacts:

```text
<task-folder>/checklist.md
<task-folder>/implementation.md
```

## Workflow

1. Read `<task-folder>/planning.md` if present.
2. Read `<task-folder>/analysis.md` if present.
3. Read or create `<task-folder>/checklist.md`.
4. Pick the first unchecked task.
5. Select required subagent(s) for that task.
6. Select required skill(s) for that task.
7. Implement only that task.
8. Run or document relevant verification.
9. Update checklist checkbox to `- [x]` only if done.
10. Append implementation notes to `<task-folder>/implementation.md`.
11. Continue with the next unchecked task only if still within the requested scope.

## Subagent selection examples

- Backend domain/application task: `ddd-modeling`, `symfony-architecture`.
- Workflow/permission task: `workflow-permissions`, `testing-security-reviewer`.
- Async task: `async-messaging`.
- Search/read model task: `search-read-models`.
- Cache/performance task: `cache-performance`.
- Frontend context task: `frontend-context-architect`.
- Frontend auth task: `frontend-identity-access`.
- Frontend UI task: `frontend-ui-composition`.
- Review task: `branch-architecture-reviewer` or `frontend-reviewer`.

## Checklist format

Use checkbox syntax only:

```markdown
# Implementation Checklist: <task title>

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

### Files changed

### Summary

### Verification

### Risks / follow-up
```

## Boardly rules

- Do not implement broad rewrites unless the checklist explicitly requires it.
- Do not skip architecture boundaries to finish faster.
- Do not mark a checkbox done if code was not changed or verification was not performed/documented.
- Do not read secrets, SQL dumps, local DB files, or private keys.
- For backend work, preserve DDD/Hexagonal boundaries and DB source of truth.
- For frontend work, preserve ADR-0006, memory-only access token, and HttpOnly refresh cookie rules.
