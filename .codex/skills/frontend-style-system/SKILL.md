---
name: frontend-style-system
description: "Apply Boardly ADR-0007 frontend styling rules: Tailwind CSS, CSS variables, shadcn-style project-owned primitives, Radix primitives, semantic tokens, and shared UI boundaries."
---

# Frontend Style System Skill

Use this skill when a task touches frontend styling, Tailwind, CSS variables, shared UI primitives, shadcn-style components, Radix primitives, design tokens, variants, accessibility primitives, or visual consistency.

## Source of truth

Always load and follow:

- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`

Also consider:

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md` for context/layer placement
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md` when styling affects frontend skeleton/app setup

## Workflow

1. Identify whether the UI is shared/generic or context-specific.
2. Place generic primitives in `frontend/src/shared/ui/`.
3. Place product/context UI in `frontend/src/contexts/<context>/presentation/`.
4. Use Tailwind as the default styling system.
5. Use CSS custom properties for theme-level semantic tokens.
6. Use `cn()` for class merging.
7. Use class-variance-authority when variants become non-trivial.
8. Use Radix primitives for accessibility-sensitive UI when needed.
9. Avoid adding many primitives before real screens require them.
10. Check that shared UI does not import product contexts.

## Output format

## 1. Summary

Short styling decision.

## 2. Source guidance loaded

List ADRs/rules used.

## 3. Placement

Shared UI vs context presentation decision.

## 4. Tokens and variants

CSS variables, Tailwind classes, `cn()`, CVA usage.

## 5. Accessibility

Radix or native accessibility decision.

## 6. Risks

Repeated class strings, product-specific shared components, token drift, overbuilt design system.

## 7. Verification

Lint/typecheck/build/component smoke checks.

## Boardly rules

- Tailwind CSS is the default frontend styling system.
- Do not introduce another broad styling system without a new ADR.
- Theme-level tokens use semantic CSS custom properties.
- Shared UI primitives must be generic and context-free.
- Context-specific UI stays in context `presentation`.
- shadcn-style components are project-owned code, not black-box magic.
- Do not accept generated UI code blindly.
- Do not create a large design system before repeated UI needs exist.
- Do not use Tailwind classes as a replacement for architecture boundaries.
