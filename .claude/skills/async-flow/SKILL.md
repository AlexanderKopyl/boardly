---
name: async-flow
description: "Design Boardly asynchronous flows with RabbitMQ and Symfony Messenger: message contracts, producers, consumers, Outbox, retries, DLQ, idempotency, and observability."
---

# Async Flow Skill

Use this skill when the request is about RabbitMQ, Symfony Messenger, domain events, integration events, async handlers, Outbox Pattern, retries, DLQ, or idempotency.

## Inputs

- Domain event or side effect.
- Required reliability level.
- Consumer behavior, if known.
- Failure tolerance.

## Workflow

1. Decide whether the behavior should be synchronous or asynchronous.
2. Separate domain event from message/integration event.
3. Define message contract.
4. Define producer and consumer responsibilities.
5. Decide whether Outbox Pattern is required.
6. Define retry strategy.
7. Define dead-letter behavior.
8. Define idempotency strategy.
9. Define recovery and observability requirements.

## Output format

## 1. Summary

Short async-flow interpretation.

## 2. Sync vs async decision

Explain why the behavior is async or must remain sync.

## 3. Message contract

Name payload fields and stability expectations.

## 4. Producer and consumer

Describe responsibilities clearly.

## 5. Transaction and Outbox

State whether Outbox is required and why.

## 6. Retry and DLQ

Define retry policy and dead-letter behavior.

## 7. Idempotency

Define idempotency key and duplicate handling.

## 8. Observability

Define logs, metrics, traces, and alerts.

## 9. Risks and trade-offs

Name failure modes directly.

## Boardly rules

- RabbitMQ is for async side effects, not core consistency.
- Important state changes happen synchronously inside a DB transaction.
- Async handlers must be idempotent.
- Failed messages must be observable and recoverable.
