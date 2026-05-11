---
name: context-compaction
description: "Create a structured compaction memo for long-running Boardly work, preserving decisions, touched files, verification commands, risks, and next actions while dropping raw noisy logs."
---

# Context Compaction Skill

Use this skill when a task becomes long-running, context is getting noisy, a session needs handoff/resume, or raw tool output starts dominating the conversation.

## Goal

Preserve resumable task state without carrying unnecessary raw logs or repeated search output.

## Workflow

1. Identify current task goal and status.
2. Preserve current hypothesis and decisions made.
3. Preserve files touched/read and why they matter.
4. Preserve commands run and results.
5. Preserve failing examples and exact errors only if they affect the next decision.
6. Preserve unresolved questions and risks.
7. Preserve next concrete actions.
8. Drop raw logs, repeated search output, install noise, and irrelevant traces.
9. Save the memo if a task folder is specified.

## Artifact rule

If a task folder is specified, save or update:

```text
<task-folder>/compaction.md
```

## Artifact format

```markdown
# Context Compaction: <task title>

## Current goal

## Current status

## Decisions made

## Files touched/read

| File | Why it matters |
| --- | --- |

## Commands run

| Command | Result | Notes |
| --- | --- | --- |

## Current hypothesis

## Open questions

## Risks

## Next actions

- [ ] ...

## Raw output intentionally dropped

Short note only.
```

## Boardly rules

- Do not hide important security, permission, source-of-truth, transaction, auth-session, or ADR conflicts.
- Do not preserve secrets, tokens, SQL dump content, private keys, or sensitive env values.
- Do not replace actual verification with vague wording.
