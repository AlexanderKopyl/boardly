---
name: context-budget-audit
description: "Estimate and control Boardly task context budget: loaded guidance, ADRs, skills, artifacts, command outputs, heavy files, and compaction triggers."
---

# Context Budget Audit Skill

Use this skill before large planning/analysis/implementation/review work, when context feels bloated, when many ADRs/skills/artifacts are being loaded, or when the user asks about token/context usage.

This skill estimates context pressure. It does not provide exact model token accounting.

## Goal

Keep useful context high and waste low.

## Required input

- Task goal.
- Task folder path, if artifacts are expected.
- Candidate files/docs/artifacts to load.
- Known task type: backend, frontend, mixed, review, bugfix, feature, refactor, migration.

## Artifact rule

If a task folder is specified, save the audit to:

```text
<task-folder>/context-budget.md
```

## Estimation rule

Use rough estimation only:

```text
estimated_tokens ~= characters / 4
```

This is a heuristic, not exact accounting.

## Workflow

1. List candidate context sources.
2. Estimate rough size per source when possible.
3. Classify each source as `load-full`, `load-section`, `load-diff`, `summarize`, or `skip`.
4. Identify heavy context sources.
5. Identify repeated guidance that should stay stable for prompt caching.
6. Recommend context ordering: stable guidance first, variable task evidence later.
7. Recommend compaction if raw logs/tool output dominate.
8. Save the audit artifact.

## Context classes

### load-full

Use for small, directly relevant files.

### load-section

Use for large rulebooks/ADRs where only one section matters.

### load-diff

Use for branch review and changed files.

### summarize

Use for large logs, long command output, previous task history, and repeated tool output.

### skip

Use for unrelated files, generated artifacts, secrets, dumps, local DBs, or broad directories.

## Output format

```markdown
# Context Budget Audit: <task title>

## Task type

## Candidate context sources

| Source | Approx chars | Approx tokens | Decision | Reason |
| --- | ---: | ---: | --- | --- |

## Heavy sources

## Stable prefix candidates

## Variable evidence

## Recommended loading order

1. ...
2. ...
3. ...

## Compaction recommendation

## Skipped sources

## Risks
```

## Budget heuristics

- Small task context pack: aim for 2k-6k estimated tokens.
- Medium feature/review: aim for 4k-12k estimated tokens.
- Migration/large refactor: accept more only with explicit reason.
- Prefer task artifacts and targeted sections over full historical conversation.
- Prefer exact failing lines over full logs.
- Prefer diff/stat/name-only before full file reads.

## Tool-output hygiene

- Do not paste full logs unless necessary.
- Use `head`, `tail`, grep filters, line ranges, and summaries.
- Preserve exact error messages that affect the decision.
- Drop repeated install noise, unrelated stack traces, and generated output.

## Boardly rules

- Do not reduce context by hiding security, permission, source-of-truth, CQRS bus, frontend auth/session, ADR-0006, or ADR-0007 risks.
- Do not read or summarize secrets, SQL dumps, local DB files, private keys, or frontend `.env*` files.
- Context budget audit is advisory; correctness and safety win over token savings.
