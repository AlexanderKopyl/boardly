# /write-adr

Write or draft an Architecture Decision Record for Boardly.

Input:
$ARGUMENTS

Use when:
- an architectural decision needs to be recorded;
- alternatives and trade-offs must be documented;
- a feature introduces important decisions around modular monolith boundaries, DDD, Doctrine, Messenger, Redis, OpenSearch, permissions, workflow, deployment, or testing.

Recommended agents:
- devops-adr-documentation;
- symfony-architecture;
- ddd-modeling when domain boundaries are involved;
- async-messaging when async/eventual consistency is involved;
- search-read-models when search/read models are involved.

Recommended skills:
- adr-writing;
- feature-architecture;
- domain-modeling;
- async-flow;
- search-indexing;
- cache-performance;
- observability-operations.

Rules:
- ADRs must document trade-offs, not marketing claims.
- Do not write ADRs for decisions that were not actually made.
- Prefer modular monolith first unless there is a concrete reason otherwise.
- DB source-of-truth decisions must be explicit.
- Async/eventual consistency decisions must include failure and recovery behavior.

Output format:
# ADR: Decision title

## Status

Proposed | Accepted | Superseded

## Context

## Decision

## Alternatives considered

## Consequences

## Risks

## Follow-up actions
