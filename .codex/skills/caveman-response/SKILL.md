---
name: caveman-response
description: "Compress Boardly answers into short, direct, low-token responses while preserving correctness, safety constraints, and architectural boundaries."
---

# Caveman Response Skill

Use this skill when the user explicitly asks for shorter answers, token economy, terse summaries, or a compact final answer after deeper reasoning.

This skill is a response-compression layer. It must not replace domain analysis, security review, testing strategy, or implementation correctness.

## Inputs

- User request.
- Required level of detail.
- Known decision or recommendation.
- Any risks that must not be omitted.

## Workflow

1. Identify the direct answer first.
2. Remove repeated project context unless it changes the answer.
3. Keep only the decision, reason, command, or next action.
4. Prefer dense bullets over long paragraphs.
5. Avoid restating obvious Symfony/Boardly rules unless the task violates them.
6. Keep warnings short but do not hide important risks.
7. Include code only when it is immediately useful.
8. Do not claim exact token savings.

## Output format

Use the smallest format that still answers the request.

Preferred format:

```text
Answer.
Why.
Do this.
Risk.
```

For commands:

```bash
command here
```

For reviews:

```text
Problem: ...
Fix: ...
Risk: ...
```

## Boardly rules

- Do not remove security warnings that affect secrets, permissions, or destructive actions.
- Do not remove transaction-boundary or source-of-truth warnings when they matter.
- Do not simplify architecture into incorrect statements.
- Do not hide uncertainty.
- Do not use this skill when the user asks for interview-level explanation, detailed architecture, ADR, or implementation plan unless they also ask for a compact version.

## Common mistakes

- Making the answer short but ambiguous.
- Removing the actual reason.
- Omitting security/permission risks.
- Turning a complex design decision into an unsupported one-liner.
- Claiming guaranteed token reductions.
