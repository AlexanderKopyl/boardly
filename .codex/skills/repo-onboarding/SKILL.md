---
name: repo-onboarding
description: "Run a deterministic, narrow onboarding/discovery pass for Boardly tasks before planning, analysis, implementation, or review."
---

# Repo Onboarding Skill

Use this skill at the start of a new session, large task, unknown repository area, branch review, or implementation where the affected files are not already known.

This skill prevents blind repository traversal.

## Goal

Acquire the smallest set of repository facts needed to avoid guessing.

## Workflow

1. Confirm the task contract: goal, non-goals, constraints, and done criteria.
2. Load durable guidance first: `AGENTS.md`, relevant ADRs, `.codex/config.toml`, relevant skills.
3. Build a small repo map only for the affected area.
4. Prefer diff-first reads for branch/PR/review tasks.
5. Prefer search-first reads for feature/bug tasks.
6. Shortlist candidate files and explain why each file is relevant.
7. Read only the top candidate files first.
8. Expand one hop only when needed: interface, implementation, test, config, migration, route, worker, or gateway.
9. Stop and ask for missing task information if no file crosses the relevance threshold.

## Recommended commands

```bash
git status --short
git branch --show-current
git diff --name-only --diff-filter=ACMRTUXB main...HEAD
git diff --stat main...HEAD
git grep -n "<identifier-or-error>" -- ':!vendor' ':!node_modules' ':!frontend/node_modules'
rg -n --glob '!{vendor,node_modules,dist,build,.git}' '<identifier-or-error>' .
```

## Artifact output

If a task folder is specified, save discovery notes to:

```text
<task-folder>/onboarding.md
```

## Artifact format

```markdown
# Onboarding: <task title>

## Task contract

## Durable guidance loaded

## Candidate files

| File | Reason | Confidence |
| --- | --- | --- |

## Files read

## Facts learned

## Still unknown

## Recommended next skill
```

## Boardly rules

- Do not use MemPalace for simple repo discovery.
- Do not read secrets, SQL dumps, local DB files, or private keys.
- Do not traverse unrelated directories.
- Do not assume missing modules/classes/entities exist.
