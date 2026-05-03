---
name: workflow-design
description: "Design Boardly issue workflows: statuses, transitions, guards, validators, permission rules, failure cases, and Symfony Workflow Component fit/no-fit decision."
---

# Workflow Design Skill

Use this skill when the request is about issue lifecycle, statuses, transitions, workflow rules, guards, validators, or Symfony Workflow Component usage.

## Inputs

- Workflow requirement or action.
- Current status and target status, if known.
- Actor and project role, if known.
- Business constraints.

## Workflow

1. Define the business meaning of the workflow.
2. Identify statuses and transitions.
3. Define guards and validators.
4. Define permission rules for each transition.
5. Define failure cases and error semantics.
6. Decide whether Symfony Workflow Component fits.
7. Identify audit requirements.
8. Identify synchronous and asynchronous side effects.

## Output format

## 1. Summary

Short workflow interpretation.

## 2. Statuses

List statuses and their business meaning.

## 3. Transitions

List allowed transitions and who can perform them.

## 4. Guards and validators

Separate permission guards from business validators.

## 5. Failure cases

Describe invalid transition, missing permission, stale state, and conflicting update cases.

## 6. Audit requirements

State what must be logged.

## 7. Symfony Workflow Component fit

Say whether to use it, why, and trade-offs.

## 8. Risks and trade-offs

Name risks directly.

## Boardly rules

- Workflow logic must not live in controllers.
- Workflow logic must not live only in frontend.
- Transition validation must happen synchronously.
- Workflow rules must not be scattered across controllers, forms, frontend, and handlers.
