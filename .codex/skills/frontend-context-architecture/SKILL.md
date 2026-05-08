---
name: frontend-context-architecture
description: "Design Boardly frontend contexts using ADR-0006 context-based hexagonal architecture: app, shared, contexts, domain, application, infrastructure, and presentation boundaries."
---

# Frontend Context Architecture Skill

Use this skill when the task is about frontend structure, context boundaries, Next.js folders, layer responsibilities, imports, or ADR-0006 compliance.

## Source of truth

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`

## Workflow

1. Identify the active frontend product context.
2. Decide whether a new context is justified.
3. Map files to `domain`, `application`, `infrastructure`, and `presentation`.
4. Keep `app/` for routing/layout/page composition only.
5. Keep `shared/` generic and context-free.
6. Check import direction and cross-context coupling.
7. Prevent frontend models from becoming backend business authority.
8. Avoid empty future contexts.

## Output format

## 1. Summary

Short decision.

## 2. Context boundary

Which context owns the UI/use case and why.

## 3. Layer mapping

| Layer | Responsibility | Files |
| --- | --- | --- |

## 4. Import rules

Allowed and forbidden dependencies.

## 5. ADR-0006 risks

List boundary risks.

## 6. Recommendation

Minimal structure to create now.

## Boardly rules

- Frontend is organized by product context, not flat technical buckets.
- Frontend domain models are not backend aggregates.
- Backend remains source of truth for business invariants.
- Do not create future contexts before a real slice needs them.
