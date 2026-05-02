---
name: testing-strategy
description: Design Boardly test coverage for domain, application, infrastructure, async flows, permissions, workflow transitions, search consistency, audit, and regression risks.
---

# Testing Strategy Skill

Use this skill when the request is about test design, quality gates, regression risks, permission tests, workflow tests, async tests, integration tests, or acceptance criteria validation.

## Inputs

- Feature or architecture flow.
- Critical business behavior.
- Security and consistency concerns.
- External dependencies, if any.

## Workflow

1. Identify the critical behavior that must not break.
2. Split tests by layer: domain, application, infrastructure, UI/API, async.
3. Define permission and visibility tests.
4. Define workflow transition tests.
5. Define idempotency and retry tests for async handlers.
6. Define search/projection consistency tests.
7. Define audit correctness tests.
8. Identify edge cases and abuse cases.
9. Define regression risks and minimum quality gate.

## Output format

## 1. Summary

Short testing interpretation.

## 2. Critical behavior

What must be protected by tests.

## 3. Test matrix

| Layer | What to test | Why |
| --- | --- | --- |
| Domain | Example | Example |

## 4. Permission and visibility tests

List authorization scenarios.

## 5. Async/idempotency tests

List retry, duplicate, and failure scenarios.

## 6. Search/projection tests

List consistency and reindexing scenarios.

## 7. Audit tests

List important audit expectations.

## 8. Edge cases

List boundary and failure cases.

## 9. Quality gate

Define what must pass before merging.

## Boardly rules

- Prioritize issue lifecycle, workflow transitions, permissions, async idempotency, search consistency, audit, and reporting projections.
- Do not rely only on controller tests.
- Domain rules should have fast unit tests.
- Infrastructure behavior should have integration tests.
