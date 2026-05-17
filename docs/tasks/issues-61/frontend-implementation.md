# Frontend Implementation: Issue 61

## 2026-05-17 - Task: Wire Tailwind CSS into the `frontend/` workspace and confirm the app can compile utility classes

### Subagents used

- `default` subagent for a quick setup recommendation on the Tailwind/PostCSS wiring.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0004-use-api-first-symfony-backend-with-nextjs-frontend.md`
- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-planning.md`
- `docs/tasks/issues-61/analysis.md`
- `docs/tasks/issues-61/frontend-checklist.md`

### Files changed

- `frontend/package.json`
- `frontend/package-lock.json`
- `frontend/postcss.config.mjs`
- `frontend/src/app/globals.css`
- `docs/tasks/issues-61/frontend-checklist.md`

### Summary

- Added the Tailwind runtime packages to the frontend workspace.
- Wired PostCSS to use `@tailwindcss/postcss`.
- Imported Tailwind at the top of the global stylesheet so the app entrypoint can compile utility classes already present in the frontend routes.

### Verification

- `npm run build` in `frontend/` succeeded.
- Next.js compiled the app and generated all routes, including the utility-class-heavy project pages.

### Risks / follow-up

- `frontend/src/app/globals.css` still contains the legacy bespoke global component styling and will be simplified in the next checklist slice.
- `frontend/tailwind.config.ts` remains in place as a legacy bridge and may be removed or repurposed once the token baseline is migrated.

## 2026-05-17 - Task: Put the semantic CSS token baseline on a clean foundation in `frontend/src/app/globals.css` while keeping only true global/base rules there

### Subagents used

- `worker` subagent (`/root/globals_css_audit`) for a focused audit of what should remain global versus what should move out of `globals.css`.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Files changed

- `frontend/src/app/globals.css`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-verification.md`
- `docs/tasks/issues-61/frontend-implementation.md`

### Summary

- Reduced `globals.css` to the semantic CSS token baseline plus only true global/base rules.
- Removed the legacy `.ui-*` component styling from the global stylesheet so the upcoming primitive rebuild can own component presentation instead of relying on shared global CSS.
- Kept the token surface intact for the existing Tailwind theme mapping and app-wide base behavior.

### Verification

- `npm run build` in `frontend/` succeeded after the cleanup.

### Risks / follow-up

- Existing screens still reference the old `.ui-*` styling contract, so they will remain visually degraded until the shared primitives and screen consumers are migrated in the next checklist slices.
- The next unchecked item is the shared class-composition helper, which should be implemented before rebuilding the shared primitives.

## 2026-05-17 - Task: Replace `frontend/src/shared/lib/cn.ts` with a class-composition helper suitable for shared primitives

### Subagents used

- `worker` subagent (`/root/cn_helper_slice`) for a focused helper-surface implementation check.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Files changed

- `frontend/src/shared/lib/cn.ts`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-verification.md`
- `docs/tasks/issues-61/frontend-implementation.md`

### Summary

- Replaced the trivial `filter(Boolean).join(' ')` helper with a recursive class-composition helper.
- Added a public `ClassValue` type so shared primitives can pass strings, numbers, arrays, and keyed class maps without depending on a third-party utility.

### Verification

- `npm run typecheck` in `frontend/` succeeded after the helper rewrite.

### Risks / follow-up

- The helper now supports the composition patterns needed by the upcoming primitive rebuild, but it intentionally does not add Tailwind merge/deduplication behavior yet.
- The next unchecked item is rebuilding `frontend/src/shared/ui/Button.tsx` as the first shared primitive on top of this helper.

## 2026-05-17 - Task: Rebuild `frontend/src/shared/ui/Button.tsx` as a shadcn-style primitive with clear variants and loading/disabled behavior

### Subagents used

- Worker subagent request attempted for the `Button` slice, but the spawn did not attach in this environment, so the implementation was completed directly in the main thread.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Files changed

- `frontend/src/shared/ui/Button.tsx`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Summary

- Replaced the old `ui-button` global-class dependency with a self-contained Tailwind primitive.
- Added explicit size and variant class maps so shared callers can rely on a stable, token-driven API.
- Kept disabled/loading behavior inside the primitive, including `aria-busy`, disabled state handling, and a small loading spinner.

### Verification

- `npm run typecheck` in `frontend/` succeeded after the button rebuild.

### Risks / follow-up

- The primitive is now independent, but any direct `className="ui-button"` consumer still needs to be migrated to the shared `Button` component in a later checklist slice.
- The next unchecked item is rebuilding `frontend/src/shared/ui/Input.tsx` as a token-driven primitive.

## 2026-05-17 10:18 EEST - Task: Rebuild `frontend/src/shared/ui/Input.tsx` as a token-driven primitive with invalid/focus/disabled states

### Subagents used

- `worker` subagent (`/root/input_primitive_slice`) for a focused review of the Input primitive contract. The worker did not return before the implementation was completed, so the code change was made directly in the main thread.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Files changed

- `frontend/src/shared/ui/Input.tsx`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Summary

- Rebuilt `Input` as a self-contained Tailwind primitive instead of relying on the removed `ui-input` global class contract.
- Added token-driven base styles for size, borders, background, placeholder, focus, disabled, and opacity behavior.
- Kept invalid-state handling inside the primitive through `aria-invalid`/`data-invalid` plus destructive border and ring treatment.

### Verification

- `npm run typecheck` in `frontend/` succeeded after the Input rebuild.
- `npm run build` in `frontend/` succeeded after the Input rebuild.

### Risks / follow-up

- The next unchecked item is migrating the current baseline auth and shell screens to the new shared styling foundation.

## 2026-05-17 - Task: Put the semantic CSS token baseline on a clean foundation in `frontend/src/app/globals.css` while keeping only true global/base rules there

### Subagents used

- `frontend-reviewer` for a focused read on what should stay in globals.css versus what would be better left to shared primitives or future theme layers.

### Skills used

- `frontend-task-implementation`
- `frontend-style-system`

### Guidance loaded

- `AGENTS.md`
- `docs/development/frontend/boardly-frontend-developer-rules.md`
- `docs/adr/0007-use-tailwind-css-with-css-variables-and-shadcn-radix-primitives.md`
- `docs/tasks/issues-61/frontend-planning.md`
- `docs/tasks/issues-61/analysis.md`
- `docs/tasks/issues-61/frontend-checklist.md`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Files changed

- `frontend/src/app/globals.css`
- `docs/tasks/issues-61/frontend-implementation.md`
- `docs/tasks/issues-61/frontend-verification.md`

### Summary

- Kept the semantic CSS token baseline and base reset rules in `globals.css`.
- Removed the duplicate `font-size` ownership from `body` so the document scale is defined in one place.
- Removed the `color-scheme: light` freeze so the global foundation stays neutral for future theme work.

### Verification

- `npm run build` in `frontend/` succeeded after the cleanup.

### Risks / follow-up

- `globals.css` still carries the private raw palette tokens that support the current Tailwind theme bridge; that remains acceptable for now, but future theme cleanup may move them elsewhere.
- The next checklist slice is still the shared `Input` primitive rebuild.
