---
name: adr-writing
description: Write Boardly architecture decision records: context, decision, alternatives, consequences, risks, follow-up actions, and explicit trade-offs.
---

# ADR Writing Skill

Use this skill when the request is about documenting an architectural decision, comparing alternatives, recording a trade-off, or explaining why a design choice was made.

## Inputs

- Decision topic.
- Context and constraints.
- Alternatives considered.
- Chosen direction, if already known.
- Risks and consequences, if known.

## Workflow

1. Define the decision context.
2. State the decision clearly.
3. List realistic alternatives.
4. Explain why the chosen decision fits Boardly now.
5. Name consequences and trade-offs.
6. Name risks and mitigation.
7. Define follow-up actions.
8. Keep the ADR practical and implementation-relevant.

## Output format

# ADR: Decision title

## Status

Proposed | Accepted | Superseded

## Context

Describe the problem and constraints.

## Decision

State the chosen direction.

## Alternatives considered

List alternatives and why they were not selected now.

## Consequences

Describe positive and negative consequences.

## Risks

Name risks directly.

## Follow-up actions

List concrete next steps.

## Boardly rules

- ADRs must document trade-offs, not marketing claims.
- Prefer modular monolith first unless there is a strong reason otherwise.
- DB source-of-truth decisions must be explicit.
- Async/eventual consistency decisions must explain failure handling.
- Do not document decisions that have not actually been made.
