---
name: caveman-response
description: "Compress Claude responses for Boardly tasks: short answers, minimal prose, no repeated context, no unnecessary explanations, and token-efficient output when the user asks for brevity or the task is simple."
---

# Caveman Response Skill

Use this skill when the user wants a short answer, quick decision, compact review, or token-efficient response.

This skill optimizes for fewer tokens, not for lower quality.

Do not claim a fixed token reduction percentage. Token savings depend on the task.

## When to use

Use when:

- the user asks for short, direct, compressed, or no-fluff output;
- the task is simple;
- the answer does not require deep explanation;
- the user is iterating quickly;
- the same context was already explained earlier;
- the response would otherwise repeat project documentation.

Do not use when:

- the user asks for detailed reasoning;
- architecture trade-offs must be explained;
- security risks need careful treatment;
- an ADR, implementation plan, or design review needs full structure;
- ambiguity would make a short answer dangerous or misleading.

## Workflow

1. Answer the exact question first.
2. Remove repeated context.
3. Remove generic explanations.
4. Use compact bullets or a small table.
5. Keep only decisions, blockers, and next actions.
6. Reference documents instead of restating them.
7. Stop when the useful answer is complete.

## Output style

Prefer:

```text
Yes. Add X and Y. Do not add Z.
Reason: ...
Next: ...
```

Avoid:

```text
In modern software engineering, it is often important to consider...
```

## Output format

## Short answer

One to three sentences.

## Decision

What to do / not do.

## Next action

One concrete next step.

## Boardly rules

- Do not remove critical security or architecture warnings just to save tokens.
- Do not read extra docs unless the task requires them.
- Do not restate ADRs or design docs.
- Prefer references to existing docs over copied explanations.
