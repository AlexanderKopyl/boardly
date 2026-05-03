---
name: cache-performance
description: "Design Boardly Redis/cache and performance strategy: hot paths, TTL, invalidation, cache keys, stale data tolerance, DB query risks, locks, and rate limiting."
---

# Cache & Performance Skill

Use this skill when the request is about Redis, caching, TTL, invalidation, rate limiting, locks, hot paths, board/dashboard performance, permission cache, or database query risks.

## Inputs

- Feature or read path.
- Data source.
- Expected traffic or hot path.
- Stale-data tolerance.
- Invalidation trigger, if known.

## Workflow

1. Identify the hot path or performance risk.
2. Confirm source-of-truth data.
3. Decide whether caching is justified.
4. Define cache key shape.
5. Define TTL and stale-data tolerance.
6. Define invalidation strategy.
7. Identify DB query risks.
8. Decide whether Redis locks or rate limiting are justified.
9. Define fallback behavior.

## Output format

## 1. Summary

Short performance interpretation.

## 2. Hot path

Describe what may become slow and why.

## 3. Source of truth

State what owns the persistent data.

## 4. Cache strategy

Define cache keys, values, TTL, and stale-data tolerance.

## 5. Invalidation

Define invalidation events and failure behavior.

## 6. Query risks

Name N+1, missing indexes, large joins, pagination, sorting, and filtering risks where relevant.

## 7. Redis boundaries

State what Redis may and may not store.

## 8. Locks / rate limiting

Use only when justified.

## 9. Risks and trade-offs

Name operational and correctness risks directly.

## Boardly rules

- Redis is not source of truth.
- Do not cache persistent issue/project/workflow/audit state as authoritative state.
- Prefer short TTL for derived counters and dashboard data.
- Permission cache must have safe invalidation.
