---
name: observability-operations
description: "Design Boardly operational readiness: queues, workers, health checks, logs, metrics, traces, retries, DLQ, stale projections, recovery commands, and deployment risks."
---

# Observability & Operations Skill

Use this skill when the request is about production readiness, workers, queues, health checks, logs, metrics, tracing, deployment risks, recovery commands, failed messages, stale projections, or operational incidents.

## Inputs

- Feature, subsystem, or operational concern.
- External dependencies involved.
- Failure modes, if known.
- Recovery expectations.

## Workflow

1. Identify operational risk.
2. Define health checks.
3. Define logs, metrics, and traces.
4. Define queue/worker behavior if relevant.
5. Define retry and dead-letter behavior if relevant.
6. Define recovery commands.
7. Define deployment and migration risks.
8. Define alerting signals.
9. Identify manual runbook steps.

## Output format

## 1. Summary

Short operations interpretation.

## 2. Operational risk

Name what can fail in production.

## 3. Health checks

Define liveness/readiness/dependency checks.

## 4. Logs, metrics, traces

Define what should be observable.

## 5. Queues and workers

Define worker behavior, backlog monitoring, and failure handling.

## 6. Recovery commands

Define commands for rebuilding cache/search/projections or replaying failures.

## 7. Deployment risks

Call out migration, backward compatibility, and rollout concerns.

## 8. Runbook notes

Provide practical operational actions.

## 9. Risks and trade-offs

Name operational trade-offs directly.

## Boardly rules

- Failed messages must be visible.
- Search indexing lag must be measurable.
- Stale projections must be detectable.
- Recovery commands must be planned for cache/search/projections.
- Audit gaps are production-critical.
