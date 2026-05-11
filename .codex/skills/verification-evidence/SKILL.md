---
name: verification-evidence
description: "Define and record exact verification evidence for Boardly changes: commands, expected results, actual results, failures, and remaining risks."
---

# Verification Evidence Skill

Use this skill after implementation, during review, before marking checklist items done, or when the user asks whether a task is actually verified.

## Goal

Make verification explicit and evidence-based.

## Workflow

1. Identify the changed area.
2. Choose the narrowest relevant verification command first.
3. Add broader checks only when needed.
4. Record exact commands.
5. Record actual results, not guesses.
6. If commands cannot be run, state why and provide the closest manual verification path.
7. Do not mark implementation checklist items as done without verification or documented limitation.
8. Save verification notes if a task folder is specified.

## Artifact rule

If a task folder is specified, save or update:

```text
<task-folder>/verification.md
```

## Suggested commands

Backend examples:

```bash
composer validate
vendor/bin/phpunit
bin/console lint:container
bin/console lint:yaml config
bin/console doctrine:schema:validate
```

Frontend examples:

```bash
pnpm lint
pnpm test
pnpm typecheck
pnpm build
```

Git examples:

```bash
git diff --check
git status --short
git diff --name-only main...HEAD
```

Use only commands that fit the current project setup.

## Artifact format

```markdown
# Verification: <task title>

## Changed area

## Commands run

| Command | Result | Notes |
| --- | --- | --- |

## Manual checks

## Failures

## Not run

| Command | Reason |
| --- | --- |

## Final verification status

VERIFIED | PARTIALLY_VERIFIED | NOT_VERIFIED

## Remaining risks
```

## Boardly rules

- Do not claim tests passed if they were not run.
- Do not hide failing tests.
- Do not mark checklist items done without verification or an explicit documented limitation.
- Prefer nearest tests before broad expensive suites.
