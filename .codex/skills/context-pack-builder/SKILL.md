---
name: context-pack-builder
description: "Build a minimal relevance-first task context pack for Boardly work: must-read files, maybe-read files, do-not-read files, evidence, and loading order."
---

# Context Pack Builder Skill

Use this skill before planning, analysis, implementation, or review when the relevant files/docs are not already obvious.

This skill turns broad repository uncertainty into a small, explicit context pack.

## Goal

Build the smallest useful task context pack.

A context pack is not a full repository summary. It is a ranked, evidence-backed list of what should be loaded for the current task and why.

## Required input

- Task goal.
- Task folder path, if artifacts are expected.
- Known files, symbols, errors, endpoints, commands, or changed paths, if provided.
- Target area: backend, frontend, mixed, review, bugfix, feature, refactor, or migration.

## Artifact rule

If a task folder is specified, save the artifact to:

```text
<task-folder>/context-pack.md
```

If the folder does not exist and file writes are allowed, create it.

## Workflow

1. Restate the task contract in one paragraph.
2. Start from explicit user-provided paths/symbols/errors.
3. Prefer diff-first discovery for branch/PR/review tasks.
4. Prefer symbol/search-first discovery for feature/bug tasks.
5. Rank candidate files by relevance.
6. Split files/docs into `must-read`, `maybe-read`, and `do-not-read`.
7. Explain why each `must-read` item is needed.
8. Define loading order.
9. Define what evidence would justify opening each `maybe-read` item.
10. Save the context pack artifact.

## Relevance signals

Rank higher when a file/doc:

- is directly named in the task;
- appears in the current diff;
- contains the failing symbol/error/endpoint;
- owns the API contract, command, query, controller, handler, DTO, route, migration, frontend context, or test touched by the task;
- is an accepted ADR/developer rulebook relevant to the task;
- is a direct neighbor of changed code;
- is required for verification.

Rank lower when a file/doc:

- is generic background;
- is unrelated to the changed path;
- is a large log or generated output;
- belongs to another bounded context without evidence;
- is a future/planned context not touched by the current task.

## Output format

```markdown
# Context Pack: <task title>

## Task contract

## Discovery method

- Diff-first / search-first / symbol-first / user-provided paths

## Must-read

| Path | Reason | Load mode |
| --- | --- | --- |

Load mode examples:

- full file
- section only
- diff only
- symbols only
- command output summary only

## Maybe-read

| Path | Open only if | Reason |
| --- | --- | --- |

## Do-not-read

| Path/pattern | Reason |
| --- | --- |

## Required guidance

## Evidence gathered

## Loading order

1. ...
2. ...
3. ...

## Next recommended skill
```

## Tool-output hygiene

- Prefer `git diff --name-only`, `git diff --stat`, `git grep`, `rg`, and targeted file reads.
- Do not paste full logs into the context pack.
- Preserve exact error lines only when they affect the next decision.
- Use ranges, filters, and summaries for large outputs.

## Boardly rules

- Do not use MemPalace for simple repo discovery.
- Do not read secrets, SQL dumps, local DB files, private keys, or frontend `.env*` files.
- Do not load whole directories when a symbol search is enough.
- Do not treat context pack recommendations as implementation.
- Backend source-of-truth, frontend auth/session, ADR-0006, ADR-0007, and CQRS bus boundaries must remain visible when relevant.
