# Async Message Handler Rules

## 1. Purpose

This document defines production rules for asynchronous message handlers in Boardly.

It applies to handlers that consume messages from Messenger/RabbitMQ, outbox publishing, domain-event-derived messages, projection updates, notifications, audit, search indexing, reporting, integrations, imports, and other background side effects.

Async delivery must be treated as at-least-once delivery.

```text
A message handler may receive the same message more than once.
```

Therefore, every durable async side-effect handler must be idempotent.

---

## 2. Core Rule

Async message handlers must be safe under duplicate delivery, retries, worker restarts, and partial failures.

Default rule:

```text
If a handler writes durable state or calls an external side effect, it must have an idempotency strategy.
```

Examples of durable side effects:

- creating audit records;
- updating read models;
- indexing documents in OpenSearch / Elasticsearch;
- sending notifications;
- creating integration records;
- synchronizing with external systems;
- generating derived records;
- mutating any database table.

Examples that usually do not require a processed-message table:

- pure logging diagnostics;
- metrics counters where duplicates are acceptable;
- recomputable cache refreshes where duplicate work is harmless;
- idempotent upsert by natural key.

Even when a processed-message table is not used, the handler must explain why duplicate delivery is safe.

---

## 3. Recommended Handler Flow

For handlers that create durable side effects, use a processed-message guard.

Expected flow:

```text
MessageHandler
-> transaction starts
-> ProcessedMessageStore::tryStart(eventId, handlerName)
-> return if already started/processed
-> execute side effect
-> ProcessedMessageStore::markProcessed(eventId, handlerName)
-> transaction commits
```

Example:

```php
public function __invoke(AccountRegisteredMessage $message): void
{
    $this->transactional->transactional(function () use ($message): void {
        if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
            return;
        }

        $this->auditRepository->add(new AuditRecord(
            id: $this->idGenerator->generate(),
            eventId: $message->eventId,
            accountId: $message->accountId,
            action: 'account_registered',
        ));

        $this->processedMessages->markProcessed($message->eventId, self::class);
    });
}
```

The processed-message record and the side effect must be committed in the same DB transaction when both are stored in the database.

---

## 4. Processed Message Identity

The idempotency key must identify both:

```text
message/event identity + handler identity
```

Recommended key:

```text
(event_id, handler_name)
```

Why handler identity is required:

```text
The same event may be handled by audit, notification, search indexing, and reporting handlers independently.
```

Bad key:

```text
event_id only
```

Because it can make the first handler prevent other handlers from processing the same event.

Good key:

```text
event_id + handler_name
```

Possible table shape:

```text
processed_messages
- event_id uuid/string not null
- handler_name varchar not null
- status varchar not null
- started_at timestamp not null
- processed_at timestamp null
- last_error text null
- attempts int not null default 0
primary key (event_id, handler_name)
```

The exact schema can be decided when the first non-diagnostic consumer is implemented.

---

## 5. Handler Responsibilities

Async handlers may:

- validate message payload shape;
- load required read/write models;
- call application services dedicated to the side effect;
- write projections/read models;
- write audit records;
- call notification ports;
- call search indexing ports;
- use processed-message/idempotency ports;
- use transactions for durable side effects.

Async handlers must not:

- execute core business commands as a hidden replacement for the original use case;
- enforce core permission checks that should have happened before the business event was produced;
- mutate aggregate state that belongs to the original synchronous use case;
- assume exactly-once delivery;
- rely on in-memory flags for idempotency;
- hide critical business mutations in Messenger handlers;
- dispatch new core commands just because an event was received;
- fail permanently on duplicate messages.

---

## 6. Transaction Rules

A handler that writes durable state should wrap these operations in one transaction:

```text
processed-message guard + side effect writes + mark processed
```

If the side effect is external and cannot share the DB transaction, the handler must use a specific idempotency strategy for that external call.

Examples:

```text
Email provider:
- use provider idempotency key if available
- or store notification record with unique event_id + handler_name + notification_type

OpenSearch:
- use deterministic document id
- use upsert/index operation safe under repeats

Audit:
- enforce unique event_id + handler_name or event_id + audit_action
```

---

## 7. Retry and Failure Rules

Handlers must be retry-safe.

Rules:

- Throw exceptions for transient failures so Messenger retry can work.
- Do not mark a message as processed before the side effect has succeeded.
- If the side effect fails, the processed-message status must not incorrectly say processed.
- Store failure details where useful for diagnostics.
- Poison messages must be inspectable through failure transport, logs, or a dedicated table.
- Retrying the same message must not create duplicate durable side effects.

Bad:

```php
$this->processedMessages->markProcessed($message->eventId, self::class);
$this->auditRepository->add(...); // may fail after processed mark
```

Good:

```php
$this->auditRepository->add(...);
$this->processedMessages->markProcessed($message->eventId, self::class);
```

inside the same transaction.

---

## 8. OutboxMessage Consumers

Outbox publishing emits transport-level `OutboxMessage` messages.

A handler may consume `OutboxMessage` directly for a small diagnostic or infrastructure-level handler.

For real business side effects, prefer explicit message classes when the consumer boundary grows.

Acceptable initial diagnostic handler:

```text
OutboxMessage -> log that identity_access.account_registered was consumed
```

Production side-effect direction:

```text
OutboxMessage
-> event_type/payload mapping
-> AccountRegisteredMessage or dedicated consumer input
-> idempotent side-effect handler
```

The message consumed by the handler must contain a stable idempotency key:

```text
event_id
```

---

## 9. Placement Rules

Shared technical contracts belong under:

```text
src/Shared/Application/...
src/Shared/Infrastructure/...
```

Context-specific consumers belong under the owning bounded context.

Examples:

```text
src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php
src/Boardly/IdentityAccess/Infrastructure/Messenger/AccountRegisteredAuditHandler.php
src/Boardly/Issues/Infrastructure/Messenger/IssueStatusChangedSearchIndexer.php
src/Boardly/Issues/Infrastructure/Messenger/IssueStatusChangedAuditHandler.php
```

Do not place all consumers into `src/Shared` just because they use Messenger.

`src/Shared` may provide generic technical support such as:

```text
ProcessedMessageStoreInterface
DoctrineProcessedMessageStore
OutboxMessage
OutboxPublisher
```

but concrete business consumers should live in their bounded contexts.

---

## 10. Review Checklist

Before adding or approving an async message handler, check:

```text
- What is the message idempotency key?
- Is the handler safe if the same message is delivered twice?
- Does the handler write durable state?
- If yes, where is the processed-message guard?
- Is handler identity part of the idempotency key?
- Are side effect writes and processed-message marking in one transaction when possible?
- What happens if the worker crashes after the side effect but before ack?
- What happens if the external service succeeds but the worker crashes before markProcessed?
- Is duplicate delivery safe for the external service?
- Does the handler avoid core aggregate mutations that should happen synchronously?
- Is the handler in the owning bounded context, not dumped into Shared?
- Are retry and failure cases testable?
```

---

## 11. Common Mistakes

### Mistake 1: Assuming exactly-once delivery

Wrong assumption:

```text
RabbitMQ/Messenger will deliver this message only once.
```

Correct assumption:

```text
The same message may be delivered more than once.
```

### Mistake 2: Marking processed too early

Wrong:

```text
mark processed -> execute side effect
```

Correct:

```text
try start -> execute side effect -> mark processed
```

### Mistake 3: Using event_id only

Wrong:

```text
processed_messages(event_id)
```

Correct:

```text
processed_messages(event_id, handler_name)
```

### Mistake 4: Making handlers mutate core state

Wrong:

```text
AccountRegistered handler activates account.
```

Correct:

```text
Account approval/activation is a synchronous command use case.
AccountRegistered handler may create audit/search/notification side effects.
```

### Mistake 5: Hiding important business behavior in async handlers

Wrong:

```text
User sees success, but core mutation actually happens later in a message handler.
```

Correct:

```text
Core mutation commits synchronously.
Async handlers update derived side effects.
```

---

## 12. Summary

Remember:

```text
Async handlers are at-least-once consumers.
Durable side effects require idempotency.
Use event_id + handler_name as the default processed-message key.
Keep the guard and durable side effect in one transaction when possible.
Do not use async handlers for hidden core business mutations.
```
