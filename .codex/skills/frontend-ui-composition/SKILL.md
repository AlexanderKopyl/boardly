---
name: frontend-ui-composition
description: "Design Boardly Next.js and React presentation composition: app routes, layouts, providers, UI components, hooks, guards, forms, and display-level state."
---

# Frontend UI Composition Skill

Use this skill when the task is about Next.js pages, layouts, providers, React components, hooks, forms, guards, or UI composition.

## Source of truth

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`

## Workflow

1. Identify whether the UI belongs to `app`, `shared/ui`, or a context `presentation` layer.
2. Keep `app/` focused on routing, layouts, and page composition.
3. Keep shared UI primitives generic and context-free.
4. Keep context presentation focused on rendering, user interaction, form state, display validation, hooks, and guards.
5. Route business/application actions through use cases or context hooks.
6. Keep API contract mapping and token refresh behavior out of components.
7. Define component/hook tests where behavior matters.

## Output format

## 1. Summary

Short UI composition decision.

## 2. File placement

Where each UI piece belongs and why.

## 3. Component responsibilities

What each component/hook/guard owns.

## 4. Forbidden leakage

What must not be inside presentation.

## 5. Tests

Required component/hook tests.

## Boardly rules

- Pages compose; they do not build raw API requests.
- Presentation renders and handles UI interaction; it does not own backend business decisions.
- Presentation must not contain token refresh/retry algorithms.
- Shared UI must not depend on product contexts.
