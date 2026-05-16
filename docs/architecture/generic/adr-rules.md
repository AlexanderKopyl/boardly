# Generic ADR Rules

Status: reusable architecture rulebook  
Scope: project-agnostic Architecture Decision Record rules.

This document must not contain product-specific modules, entity names, routes, table names, or examples from a concrete project.

## 1. Purpose

Architecture Decision Records document important technical and architectural decisions.

An ADR explains:

```text
- what decision was made;
- why it was made;
- which alternatives were considered;
- what trade-offs were accepted;
- how the decision should be adopted;
- what risks remain.
```

ADRs are not changelogs and not implementation plans, although they may include adoption guidance.

## 2. When an ADR is required

Create an ADR for decisions about:

```text
- source code structure;
- layer boundaries;
- module/bounded-context boundaries;
- aggregate ownership;
- database schema strategy;
- transaction boundaries;
- event/outbox strategy;
- queue usage;
- cache/search consistency;
- authorization model;
- authentication model;
- integration strategy;
- framework component adoption;
- migration from legacy architecture to target architecture;
- deployment/runtime architecture when it affects code design.
```

Do not create ADRs for every small implementation detail.

## 3. ADR status rules

Use clear statuses:

```text
Proposed
Accepted
Superseded
Deprecated
Rejected
```

Accepted ADRs are source-of-truth until superseded or updated.

If a decision changes, do not silently rewrite history. Add a new ADR or clearly mark the old ADR as superseded.

## 4. ADR content structure

Recommended structure:

```text
# ADR-XXXX: Decision title

## Status
## Date
## Context
## Decision
## Scope
## Rules
## Alternatives Considered
## Consequences
## Trade-offs
## Impact on Layers
## Migration / Adoption Plan
## Risks
## Mitigations
## Open Questions
## References
```

Use only the sections needed for the decision, but do not omit context, decision, alternatives, and consequences.

## 5. Context rules

Context should explain the problem and constraints.

Good context includes:

```text
- current situation;
- pain points;
- constraints;
- forces/trade-offs;
- why a decision is needed now.
```

Do not make context a sales pitch.

## 6. Decision rules

Decision must be explicit and testable.

Bad:

```text
Use clean architecture where appropriate.
```

Good:

```text
New HTTP controllers must be placed under the Interfaces layer. Application must not contain controllers. Existing legacy controllers may remain until migrated through behavior-protected tasks.
```

## 7. Alternatives rules

Every important ADR should list realistic alternatives.

For each alternative, include:

```text
- pros;
- cons;
- whether accepted, rejected, or partially accepted;
- why.
```

Do not create fake alternatives that no one would choose.

## 8. Consequences and trade-offs

Consequences should be honest.

Include:

```text
- positive consequences;
- negative consequences;
- operational consequences;
- migration impact;
- testing impact;
- future constraints.
```

Architecture decisions always trade one cost for another.

## 9. Layer impact rules

When relevant, describe impact on:

```text
Domain
Application
Infrastructure
Interfaces
Database
Messaging
Cache
Search
Tests
Documentation
```

Do not claim a decision is purely technical if it changes business boundaries or use-case behavior.

## 10. Migration/adoption rules

If a decision affects existing code, include an adoption plan.

Migration plan should define:

```text
- what changes first;
- what remains temporarily tolerated;
- what new code must do;
- what old code must stop doing;
- what tests protect migration;
- what not to migrate yet.
```

## 11. Project-specific ADR rule

Generic architecture rules can be reused across projects.

ADRs are usually project-specific.

Do not copy an ADR from another project without adapting:

```text
- business context;
- runtime stack;
- database engine;
- source layout;
- team constraints;
- migration risk;
- existing code reality.
```

## 12. Anti-patterns

Avoid:

```text
- ADRs that only say "use best practices";
- ADRs without alternatives;
- ADRs that hide negative consequences;
- ADRs that describe current legacy state as target architecture;
- ADRs copied from another project with unchanged domain examples;
- ADRs that conflict with accepted ADRs without superseding them;
- ADRs used as task tickets instead of decisions.
```
