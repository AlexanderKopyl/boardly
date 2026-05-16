# Generic Legacy Migration Rules

Status: reusable architecture rulebook  
Scope: project-agnostic rules for migrating legacy backend systems toward a target architecture.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Legacy migration must distinguish current reality from target architecture.

Current code is evidence. It is not automatically permission to keep using the same placement or pattern for new code.

The goal is to improve architecture incrementally without breaking existing behavior.

## 2. Classification rule

When reviewing existing code, classify each placement or dependency as one of:

```text
TARGET_RULE
CURRENT_STATE
LEGACY_EXCEPTION
WRONG_AND_MUST_BE_FIXED
```

Definitions:

```text
TARGET_RULE        = desired architecture for new and migrated code
CURRENT_STATE      = what exists now, without judging it as correct
LEGACY_EXCEPTION   = tolerated temporarily because moving it now is risky
WRONG_AND_MUST_BE_FIXED = issue that should be fixed before or during the current task
```

Do not describe legacy exceptions as target architecture.

## 3. New code rule

New code must follow the target architecture unless the task explicitly documents an exception.

Existing legacy placement must not be copied by default.

## 4. Migration safety rule

Do not perform large structural rewrites without behavior protection.

Before moving risky code:

```text
- identify current behavior;
- identify public contracts;
- identify routes/commands/messages affected;
- add or confirm tests;
- define rollback path;
- keep the PR small enough to review.
```

## 5. Behavior preservation rule

Migration tasks should preserve behavior unless the task explicitly changes behavior.

Do not change these accidentally:

```text
- public HTTP routes;
- request/response shapes;
- database schema;
- message contracts;
- external API payloads;
- CLI command signatures;
- security behavior;
- business rules.
```

## 6. Incremental migration order

Recommended order:

```text
1. Document target architecture.
2. Mark legacy exceptions clearly.
3. Stop new code from copying legacy placement.
4. Pick one small flow or controller group.
5. Add behavior tests or smoke checks.
6. Extract request/response mapping if needed.
7. Extract application use case if needed.
8. Move infrastructure dependencies behind ports if needed.
9. Move business rules into domain model/policies if needed.
10. Update docs after the pattern is proven.
```

## 7. Documentation migration rule

Architecture documentation must separate:

```text
- current state;
- target state;
- temporary exceptions;
- migration plan.
```

Do not write documentation that makes legacy layout look like the intended design.

## 8. File move rule

Mechanical file moves and behavior refactors should usually be separate tasks.

A safe file-move task should:

```text
- preserve behavior;
- preserve public contracts;
- update service/routing config;
- update namespaces;
- run tests or route checks;
- avoid unrelated cleanup.
```

## 9. Risky flow rule

Identify risky flows before refactoring.

Risky flows usually include:

```text
- payment-like flows;
- authentication/security flows;
- external integration callbacks;
- order/booking/transactional flows;
- imports/synchronization;
- permission-sensitive actions;
- data migration paths;
- public API compatibility paths.
```

Risky flows require tests or explicit manual verification before refactor.

## 10. AI agent rule

AI agents must not infer target architecture from current folders.

Every architecture-sensitive AI task must state:

```text
- current placement;
- target placement;
- whether this is new code or migration;
- whether behavior must be preserved;
- non-goals;
- verification steps.
```

## 11. Non-goal examples for migration prompts

Common non-goals:

```text
- Do not move all controllers.
- Do not rename namespaces globally.
- Do not change public API contracts.
- Do not change database schema unless explicitly required.
- Do not introduce new packages without justification.
- Do not create generic abstractions before a second real use case exists.
- Do not place business logic in controllers.
- Do not combine formatting cleanup with architecture refactor.
```

## 12. Anti-patterns

Avoid:

```text
- big-bang rewrite;
- moving code without tests;
- treating current layout as target architecture;
- documenting tolerated exceptions as recommended patterns;
- fixing unrelated code during migration;
- renaming modules before understanding boundaries;
- introducing DDD folders without business behavior;
- creating generic abstractions to hide legacy instead of isolating it.
```

## 13. Final rule

Legacy migration is successful when each step is:

```text
small
reviewable
behavior-protected
aligned with target architecture
reversible or safely recoverable
```
