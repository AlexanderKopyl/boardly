---
name: task-planning
description: "Create an implementation plan for a Boardly task and save planning artifacts into the specified task folder."
---

# Task Planning Skill

Use this skill when the user asks to plan a feature, fix, refactor, review follow-up, or implementation task before coding.

This skill produces a durable planning artifact. Do not only answer in chat when a task folder is specified.

## Required input

- Task goal.
- Task folder path where artifacts must be saved.
- Scope constraints, if provided.
- Target area: backend, frontend, infrastructure, docs, or mixed.

If the task folder is not specified, ask for it before creating artifacts unless the user explicitly asks for a chat-only plan.

## Artifact rule

Save the planning artifact into the specified task folder.

Default artifact:

```text
<task-folder>/planning.md
```

If the folder does not exist and file writes are allowed, create it.

## Workflow

1. Confirm the task goal and scope.
2. Inspect relevant existing docs/code only as needed.
3. Identify affected bounded/context areas.
4. Select relevant subagents and skills.
5. Split the work into small implementation tasks.
6. Mark tasks with checkbox syntax.
7. Define dependencies/order.
8. Define risks, validation steps, and expected artifacts.
9. Save the plan to `<task-folder>/planning.md`.

## Required checklist syntax

Use checkbox syntax for implementation tasks:

```markdown
- [ ] Task 1
- [ ] Task 2
- [ ] Task 3
```

## Artifact format

```markdown
# Planning: <task title>

## Goal

## Scope

## Non-goals

## Relevant context

## Subagents to use

## Skills to use

## Implementation checklist

- [ ] ...

## Validation checklist

- [ ] ...

## Risks

## Open questions

## Next artifact

Expected next artifact: `<task-folder>/analysis.md`
```

## Boardly rules

- Do not plan broad rewrites for small tasks.
- Do not assume missing files/classes/entities exist.
- Keep backend source-of-truth rules explicit.
- For frontend tasks, follow ADR-0006.
- Planning does not implement code.
