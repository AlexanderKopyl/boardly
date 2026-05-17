---
name: agent-evaluation-metrics
description: "Record lightweight metrics for Boardly agent work: files opened/edited, tool usage, verification status, unsupported claims, assumptions, and context efficiency."
---

# Agent Evaluation Metrics Skill

Use this skill after planning, analysis, implementation, review, or verification when you need to evaluate agent quality, context discipline, and task reliability.

This skill is not for product analytics. It is for engineering-agent workflow quality.

## Goal

Make agent behavior measurable enough to improve prompts, skills, and workflows.

## Required input

- Task goal.
- Task folder path, if artifacts are expected.
- Available task artifacts.
- Changed files, commands run, verification status, and known failures.

## Artifact rule

If a task folder is specified, save metrics to:

```text
<task-folder>/agent-metrics.md
```

## Workflow

1. Identify the task type.
2. Record files opened/read.
3. Record files edited/created/deleted.
4. Record commands/tools used.
5. Record verification status.
6. Record opened-but-unused files if obvious.
7. Record failed assumptions or corrected assumptions.
8. Record unsupported claims, if any.
9. Record safety events: denied secret reads, approvals requested, destructive actions avoided.
10. Record follow-up improvements for prompts/skills/tooling.

## Metrics to track

- Task type.
- Lifecycle used.
- Subagents used.
- Skills used.
- Files opened.
- Files edited.
- Opened-but-unused files.
- Commands run.
- Commands failed.
- Verification status.
- Unsupported claims.
- Assumptions made.
- Assumptions corrected.
- Safety blocks triggered.
- Context compaction used.
- Estimated context budget artifact present: yes/no.

## Output format

```markdown
# Agent Metrics: <task title>

## Task type

## Lifecycle used

## Subagents used

## Skills used

## Files opened/read

| File | Used for | Useful? |
| --- | --- | --- |

## Files changed

| File | Change type | Reason |
| --- | --- | --- |

## Commands/tools used

| Command/tool | Purpose | Result |
| --- | --- | --- |

## Verification status

VERIFIED | PARTIALLY_VERIFIED | NOT_VERIFIED | REJECTED

## Unsupported claims

## Assumptions made

## Assumptions corrected

## Safety/approval events

## Context discipline notes

## Improvements for next run

- [ ] ...
```

## Quality signals

Good signs:

- changed files match planned scope;
- most opened files were used;
- verification evidence exists;
- failures and not-run commands are disclosed;
- no unsupported architectural/security claims;
- no broad repo reads without evidence.

Bad signs:

- many opened files were not used;
- checklist marked done without verification;
- command output was summarized vaguely;
- architecture claims were made without evidence;
- MemPalace was used for simple repo discovery;
- secrets or denied files were attempted;
- implementation happened without planning/analysis when artifacts were expected.

## Boardly rules

- Do not use metrics as a substitute for verification.
- Do not store secrets, raw tokens, SQL dump content, private keys, or sensitive env values in metrics artifacts.
- Do not hide failed commands or not-run verification.
- Use metrics to improve prompts, skills, and routing, not to justify bad output.
