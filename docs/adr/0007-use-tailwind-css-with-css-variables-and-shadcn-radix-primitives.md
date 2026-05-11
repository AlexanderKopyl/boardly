# ADR-0007: Use Tailwind CSS with CSS Variables and shadcn/Radix Primitives

## Status

Accepted

## Date

2026-05-08

## Owner

- Architect: Boardly architecture owner
- Domain owner: Product / project management domain owner
- Technical owner: Frontend / product UI owner

## Context

Boardly is a Jira-like project, task, workflow, and collaboration management system.

ADR-0004 established Boardly as an API-first product with a Symfony backend and a Next.js frontend.

ADR-0006 established frontend context-based hexagonal architecture:

```text
frontend/src/
  app/
  shared/
  contexts/
```

The frontend now needs an explicit styling and UI primitive strategy before implementation starts.

Boardly will require a complex product UI:

```text
- authentication screens;
- dashboards;
- project navigation;
- issue lists;
- issue details;
- kanban boards;
- filters;
- dropdown menus;
- dialogs;
- forms;
- badges/status labels;
- sidebars;
- panels;
- loading and empty states.
```

Without a styling decision, the project risks mixing several approaches:

```text
CSS Modules
plain global CSS
component-library overrides
inline styles
runtime CSS-in-JS
ad-hoc class names
```

This would make UI consistency, theme evolution, accessibility, and long-term maintainability weaker.

## Decision

We will use:

```text
Tailwind CSS
+
CSS custom properties for design tokens
+
shadcn/ui-style shared primitives
+
Radix UI primitives where accessible behavior is needed
+
class-variance-authority for component variants when useful
```

This means:

- Tailwind is the primary styling utility system.
- CSS variables define theme tokens such as colors, radius, borders, and surfaces.
- Shared UI primitives live in `frontend/src/shared/ui/`.
- shadcn/ui is used as a code ownership approach, not as a black-box component dependency.
- Radix UI may be used for accessible primitives such as dialogs, dropdowns, popovers, selects, tooltips, and tabs.
- Context-specific UI stays inside each context's `presentation` layer.

Initial direction:

```text
frontend/src/app/globals.css
frontend/src/shared/lib/cn.ts
frontend/src/shared/ui/Button.tsx
frontend/src/shared/ui/Input.tsx
frontend/src/shared/ui/Label.tsx
frontend/src/shared/ui/Dialog.tsx
frontend/src/shared/ui/DropdownMenu.tsx
frontend/src/shared/ui/Badge.tsx
```

## Scope

This decision applies to:

- frontend styling strategy;
- shared UI primitives;
- theme tokens;
- component variants;
- future design system evolution;
- accessibility-sensitive UI primitives;
- frontend skeleton setup.

This decision does not apply to:

- backend rendering;
- Symfony/Twig operational pages;
- final visual brand identity;
- final component inventory;
- final design tool workflow;
- advanced charting/visualization libraries;
- drag-and-drop library choice.

## Architectural Rules

- Tailwind CSS is the default styling system for frontend UI.
- Do not introduce another broad styling system without a new ADR.
- CSS variables must be used for theme-level design tokens.
- Global CSS is allowed only for Tailwind layers, CSS variables, reset/base styles, and truly global layout rules.
- Shared UI primitives must live in `frontend/src/shared/ui/`.
- Shared UI primitives must stay generic and context-free.
- Context-specific UI must stay in `frontend/src/contexts/<context>/presentation/`.
- shadcn/ui-generated or shadcn-inspired components must be treated as project-owned code.
- Radix UI should be used for accessibility-sensitive primitives instead of custom ad-hoc implementations.
- Component variants should use explicit typed APIs, preferably with class-variance-authority when variants become non-trivial.
- Do not scatter long repeated Tailwind class strings across product contexts when a shared primitive is appropriate.
- Do not create a large design system before repeated UI needs exist.
- Do not use Tailwind classes as a replacement for architecture boundaries.

## Token Rules

Theme-level tokens must use semantic CSS custom properties.

Good token names:

```text
--background
--foreground
--card
--card-foreground
--popover
--popover-foreground
--primary
--primary-foreground
--secondary
--secondary-foreground
--muted
--muted-foreground
--accent
--accent-foreground
--destructive
--destructive-foreground
--border
--input
--ring
--radius
```

Weak token names:

```text
--random-blue
--button-color-1
--jira-copy-bg
--navy-thing
--alex-card
```

Rules:

- Use semantic names for reusable UI meaning.
- Keep raw color values centralized in theme tokens.
- Do not hardcode brand colors across many components.
- Prefer tokens for surfaces, text, borders, rings, and actions.
- Keep dark mode possible even if it is not implemented in the first slice.

## Component Rules

Shared component example:

```tsx
<Button variant="primary" size="md" />
```

Preferred over repeating large class strings:

```tsx
<button className="inline-flex h-10 items-center justify-center rounded-md ..." />
```

Rules:

- Shared components expose clear props such as `variant`, `size`, `disabled`, and `isLoading` only when needed.
- Avoid giant components with dozens of unrelated props.
- Use composition for complex UI.
- Keep product-specific wording and behavior outside shared UI primitives.
- Shared UI primitives must not call backend APIs.
- Shared UI primitives must not import product contexts.

## Layer Impact

### app

`app` imports global styles and composes pages/layouts.

Allowed:

```text
frontend/src/app/globals.css
frontend/src/app/layout.tsx
frontend/src/app/providers.tsx
```

Rules:

- `globals.css` contains Tailwind imports/layers and CSS variables.
- Pages should compose UI and context presentation components.
- Pages should not contain large one-off styling systems.

### shared

`shared` owns generic UI primitives and styling helpers.

Allowed:

```text
frontend/src/shared/ui/Button.tsx
frontend/src/shared/ui/Input.tsx
frontend/src/shared/lib/cn.ts
```

Rules:

- Shared UI must be context-free.
- Shared UI may use Tailwind, CSS variables, Radix primitives, and class-variance-authority.
- Shared UI must not import from `contexts/*`.

### contexts

Contexts own product-specific UI composition.

Allowed:

```text
frontend/src/contexts/identity-access/presentation/ui/LoginForm.tsx
frontend/src/contexts/issues/presentation/ui/IssueCard.tsx
frontend/src/contexts/boards/presentation/ui/BoardColumn.tsx
```

Rules:

- Context presentation components use `shared/ui` where appropriate.
- Context presentation may use Tailwind for local layout and composition.
- Repeated generic patterns should be extracted to `shared/ui` only after repetition proves the need.

## Alternatives Considered

### Option A: Plain CSS / global CSS

Pros:

- no extra framework;
- browser-native;
- simple for very small projects.

Cons:

- weak consistency for a large product UI;
- class naming and cascade management become manual;
- easy to create global side effects;
- does not provide a strong component variant story.

Rejected because Boardly is expected to grow into a complex application UI.

### Option B: CSS Modules

Pros:

- good local scoping;
- simple mental model;
- works well with Next.js;
- no runtime styling cost.

Cons:

- many small CSS files;
- variant composition can become verbose;
- design tokens still need a separate strategy;
- slower UI iteration for product screens.

Not selected as primary styling system. CSS Modules may be allowed only for exceptional cases with a clear reason.

### Option C: Runtime CSS-in-JS

Examples:

```text
styled-components
Emotion
```

Pros:

- component-scoped styling;
- powerful dynamic styling;
- familiar in some React codebases.

Cons:

- runtime overhead and SSR complexity;
- extra tooling concerns with Next.js;
- can hide design inconsistencies inside components;
- unnecessary for Boardly's initial needs.

Rejected as the default approach.

### Option D: Heavy component library

Examples:

```text
MUI
Ant Design
```

Pros:

- very fast initial UI assembly;
- many components available;
- accessible pieces included in many cases.

Cons:

- hard to create a distinct Boardly product feel;
- override complexity grows;
- bundle and theme customization can become heavy;
- product UI may start looking like the library instead of Boardly.

Rejected for the primary product UI.

### Option E: Tailwind CSS + CSS variables + shadcn/Radix primitives

Pros:

- fast product UI development;
- strong control over final UI;
- good fit for shared UI primitives;
- CSS variables give theme flexibility;
- Radix provides accessible behavior for complex primitives;
- shadcn-style components are project-owned code;
- aligns well with Next.js and TypeScript.

Cons:

- Tailwind class strings can become long;
- requires discipline to avoid copy-paste UI chaos;
- shadcn-style components are owned and maintained by the project;
- developers must understand utility-first styling.

Accepted because it gives Boardly the best balance of speed, control, maintainability, and accessibility.

## Consequences

### Positive

- UI styling has a single primary direction.
- Shared primitives can evolve into a lightweight design system.
- Product contexts can build screens without reinventing base controls.
- Design tokens can support brand consistency and future theming.
- Accessibility-sensitive primitives can use Radix instead of custom fragile code.
- The frontend skeleton can include styling from the beginning.

### Negative

- Tailwind can make JSX visually dense.
- Shared primitives must be maintained as project code.
- Developers must avoid turning `className` into unreviewed styling dumps.
- A separate visual design pass is still required for polished UI.

### Neutral / Operational

- Tailwind should be installed during frontend skeleton creation.
- The initial skeleton should include only minimal primitives needed for auth placeholders.
- Additional primitives should be added only when a real screen needs them.
- If shadcn CLI is used, generated components must be reviewed and adapted to Boardly structure.

## Trade-offs

- We accept utility classes in exchange for speed and explicitness.
- We accept project-owned UI primitives in exchange for avoiding black-box library lock-in.
- We accept token setup early in exchange for future theme consistency.
- We avoid heavy component libraries in exchange for more control over product identity.
- We defer a complete design system until repeated UI patterns justify it.

## Impact on DDD

No backend domain behavior is affected.

Frontend styling must not influence domain or application ownership.

Context-specific presentation components can express UI language for a context, but backend remains authoritative for product behavior.

## Impact on EDA

No event-driven behavior is introduced.

## Impact on CQRS

No command/query behavior is introduced.

Future read-model screens may use shared UI primitives, but styling must not change API boundaries or read-model ownership.

## Symfony Implementation Direction

No Symfony implementation changes are required.

Symfony remains the backend API and domain/application owner.

Twig remains outside the main product UI according to ADR-0004.

## Frontend Implementation Direction

The frontend skeleton task must initialize Tailwind CSS.

Initial expected files include:

```text
frontend/postcss.config.mjs
frontend/src/app/globals.css
frontend/src/shared/lib/cn.ts
frontend/src/shared/ui/Button.tsx
frontend/src/shared/ui/Input.tsx
```

Potential dependencies, using latest stable versions at implementation time:

```text
tailwindcss
@tailwindcss/postcss or current Tailwind/PostCSS integration package required by latest Tailwind
class-variance-authority
clsx
tailwind-merge
lucide-react
@radix-ui/react-slot
```

Radix component packages should be added only when needed, for example:

```text
@radix-ui/react-dialog
@radix-ui/react-dropdown-menu
@radix-ui/react-label
@radix-ui/react-popover
```

Do not install many Radix packages before actual components require them.

## Risks

- Developers may overuse raw Tailwind classes instead of extracting repeated primitives.
- Shared UI may become product-specific.
- Too many primitives may be added before real need.
- shadcn-generated code may be accepted blindly without adapting structure and accessibility details.
- Tailwind configuration may drift from CSS variables.
- Multiple styling systems may appear if not reviewed.

## Mitigations

- Keep shared UI generic and reviewed.
- Add primitives only when used by an active slice.
- Keep token names semantic.
- Use `cn()` helper for class merging.
- Use component variants for repeated states.
- Forbid broad styling systems without a new ADR.
- Review generated UI code before committing it.
- Add lint/format checks as the frontend skeleton matures.

## Migration / Adoption Plan

1. Update frontend developer rules to reference this ADR.
2. Update the frontend skeleton task to enable Tailwind.
3. Add Tailwind during frontend skeleton creation.
4. Add `globals.css` with CSS variables and Tailwind setup.
5. Add `shared/lib/cn.ts`.
6. Add minimal `Button` and `Input` primitives.
7. Use shared primitives in login/register placeholders.
8. Add more primitives only when real screens require them.

## Open Questions

- Should dark mode be implemented in the first frontend milestone or only kept possible through tokens?
- Should shadcn CLI be used directly, or should components be added manually using shadcn-style patterns?
- Which exact initial color palette should be committed for Boardly branding?
- Should import boundaries prevent contexts from importing each other's presentation components?

## References

- Related ADRs:
  - `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
  - `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- Related docs:
  - `frontend/README.md`
  - `docs/development/frontend/boardly-frontend-developer-rules.md`
- Related issues:
  - `#42 Create frontend Next.js TypeScript skeleton`
- Related PRs: none

## Review Checklist

- [x] Decision is clear and testable.
- [x] Business context is explained.
- [x] Alternatives are documented.
- [x] Trade-offs are explicit.
- [x] Layer impact is described.
- [x] DDD impact is described.
- [x] EDA impact is described if events are involved.
- [x] CQRS impact is described if commands/queries/read models are involved.
- [x] Symfony implementation is treated as implementation detail.
- [x] Risks and mitigations are documented.
