# /design-async-flow

Design an asynchronous Boardly flow with Symfony Messenger and RabbitMQ.

Input:
$ARGUMENTS

Use when:
- a domain event causes async side effects;
- notifications, audit exports, search indexing, projections, or integrations are involved;
- Outbox, retries, DLQ, idempotency, workers, or recovery commands must be designed.

Recommended agents:
- async-messaging;
- symfony-architecture;
- search-read-models when indexing/projections are involved;
- devops-adr-documentation for workers and recovery;
- testing-security-reviewer for idempotency and failure coverage.

Recommended skills:
- async-flow;
- observability-operations;
- search-indexing;
- testing-strategy.

Rules:
- RabbitMQ is for async side effects, not core consistency.
- Important state changes must happen synchronously inside a DB transaction.
- Use Outbox when reliable publication matters.
- Consumers must be idempotent.
- Failed messages must be observable and recoverable.

Output format:
1. Short answer
2. Sync vs async boundary
3. Domain event vs integration/message event
4. Message contract
5. Producer and consumer responsibilities
6. Transaction and Outbox strategy
7. Retry and DLQ strategy
8. Idempotency
9. Observability and recovery
10. Tests
11. Common mistakes
