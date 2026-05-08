---
name: frontend-review-checklist
description: "Review Boardly frontend changes against ADR-0006: context boundaries, auth safety, API integration, UI composition, import rules, and test coverage."
---

# Frontend Review Checklist Skill

Use this skill when reviewing frontend code, PRs, branches, or implementation plans against ADR-0006.

## Source of truth

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`
- `docs/adr/0005-use-jwt-access-tokens-and-http-only-refresh-cookies.md` for auth/session behavior

## Workflow

1. Identify changed frontend files.
2. Check context/layer placement.
3. Check whether `app/` only routes/composes.
4. Check whether `shared/` remains generic and context-free.
5. Check whether context `domain` has no React, Next.js, HTTP, storage, or persistence assumptions.
6. Check whether context `application` depends on ports, not concrete adapters.
7. Check whether `infrastructure` owns API contract mapping and adapter implementation.
8. Check whether `presentation` has no raw HTTP calls, token refresh algorithms, or backend error normalization.
9. Check auth token/cookie storage safety.
10. Check tests for use cases, gateways, hooks, guards, and critical UI behavior.

## Output format

## 1. Verdict

APPROVE | APPROVE_WITH_NOTES | REQUEST_CHANGES | BLOCK

## 2. Findings

### [BLOCKER|HIGH|MEDIUM|LOW] Title

- File(s): `path`
- Problem: ...
- ADR-0006 rule: ...
- Recommended fix: ...

## 3. Boundary notes

## 4. Auth/session notes

## 5. API integration notes

## 6. UI composition notes

## 7. Testing gaps

## 8. What is acceptable as-is

## Boardly rules

- Do not approve persistent access-token storage.
- Do not approve JavaScript-readable refresh tokens.
- Do not approve raw API calls scattered inside components.
- Do not approve frontend-authoritative permission/workflow decisions.
- Do not demand empty future contexts before real slices need them.
- Do not treat frontend domain models as backend aggregates.
