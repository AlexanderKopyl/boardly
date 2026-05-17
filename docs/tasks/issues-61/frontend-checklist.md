# Issue 61 Checklist

- [ ] Wire Tailwind CSS into the `frontend/` workspace and confirm the app can compile utility classes.
- [ ] Put the semantic CSS token baseline on a clean foundation in `frontend/src/app/globals.css` while keeping only true global/base rules there.
- [ ] Replace `frontend/src/shared/lib/cn.ts` with a class-composition helper suitable for shared primitives.
- [ ] Rebuild `frontend/src/shared/ui/Button.tsx` as a shadcn-style primitive with clear variants and loading/disabled behavior.
- [ ] Rebuild `frontend/src/shared/ui/Input.tsx` as a token-driven primitive with invalid/focus/disabled states.
- [ ] Migrate the current baseline auth and shell screens to the new shared styling foundation where they already depend on the old global CSS contract.
- [ ] Migrate the current projects baseline screens to the new shared styling foundation where they already contain utility-class markup.
- [ ] Verify the affected frontend surfaces with `build`, `lint`, `typecheck`, and a browser smoke test.
