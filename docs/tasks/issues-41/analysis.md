# Issue #41 Analysis: Add Async Log Handler for Approved IdentityAccess Accounts

## 1. Issue Summary

Issue #41 asks for an async handler that consumes the `AccountApproved` domain event as it flows through
the transactional outbox pipeline and writes a structured, privacy-safe log entry.

The expected pipeline end-to-end is:

```
AccountApproved domain event
-> outbox record (AccountApprovedOutboxEventSerializer)
-> AccountApprovedOutboxMessageMapper -> AccountApprovedMessage
-> AccountApprovedMessageHandler (new)
-> one info-level structured log entry
```

The safe log message is `IdentityAccess account approved.` with context fields
`event_id`, `outbox_id`, `account_id`, `approved_at`.

---

## 2. MemPalace Summary

The following points were provided by MemPalace prior to inspection:

1. **ADR-0003** establishes the transactional outbox: domain events are stored in the same DB transaction
   as the business state change; a separate publisher reads unpublished records and dispatches them to
   Messenger/RabbitMQ.

2. From a prior analysis (issue #36/ApproveAccount): `AccountApproved` was noted as NOT yet a
   `DomainEvent`; `ApproveAccountCommand`, `ApproveAccountHandler`, and `ApproveAccountResult` were
   listed as missing; no `AccountApprovedOutboxEventSerializer` existed yet.

3. Issue #33 established `OutboxMessageMapperInterface` and `OutboxMessageMapperRegistry` at
   `src/Shared/Infrastructure/Outbox/`. Flow: `OutboxRecord -> registry -> concrete Message -> Messenger async`.

4. Messenger routing: explicit per concrete class on `event.bus`; no broad `App\Boardly\*: async`
   wildcard. `command.bus` and `query.bus` stay synchronous.

5. Approve flow noted as:
   `ApproveAccountCommand(actorId, targetAccountId) -> load actor -> assert isSystemAdmin() -> load target -> target.approve() -> save -> OutboxInterface::store(produced events)`.

**Memory accuracy vs current code:**

- Point 2 is outdated. All three missing application classes now exist and are complete. `AccountApproved`
  already implements `DomainEvent`. `AccountApprovedOutboxEventSerializer` already exists. The
  `AccountApprovedOutboxMessageMapper` and `AccountApprovedMessage` also already exist.
- Point 5 is partially inaccurate. The actual `ApproveAccountHandler` looks up accounts by email, not by
  targetAccountId; there is no actor/isSystemAdmin check in the handler (the command carries only `email`).
  Current code is the authoritative source.
- Points 1, 3, 4 are confirmed accurate by the codebase.

---

## 3. Files Inspected

| File | Why inspected | Relevant finding |
|------|---------------|-----------------|
| `docs/architecture/project-architecture-rules.md` | Architecture baseline, layer rules, EDA rules | Confirms hexagonal layers; infrastructure handlers live in `src/Boardly/<Context>/Infrastructure/`; domain must not depend on Symfony/Messenger |
| `docs/architecture/async-message-handler-rules.md` | Idempotency, processed-message guard, retry rules, placement | Pure logging does not require processed-message table when duplicates are harmless, but this project uses the guard pattern for all durable diagnostic handlers |
| `docs/architecture/application-bus-rules.md` | Bus types, sync vs async, Messenger routing | ApproveAccount is synchronous; async side effects go through outbox; Messenger routing must be explicit per class |
| `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md` | Accepted ADR for outbox pattern | Confirms outbox flow; store + publish must be separate; async consumers must be idempotent |
| `config/packages/messenger.yaml` | Messenger transport, routing, bus config | `AccountRegisteredMessage` is routed to `async`; `AccountApprovedMessage` is **not yet routed**; `event.bus` uses `allow_no_handlers: true` |
| `config/services.yaml` | Service wiring, tagged services, alias mappings | `ApproveAccountHandler` is registered on `command.bus`; `OutboxMessageMapperInterface` tagged as `boardly.outbox_message_mapper`; `OutboxEventSerializerInterface` tagged as `boardly.outbox_event_serializer` |
| `src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php` | Verify DomainEvent inheritance, payload fields | Implements `DomainEvent`; fields: `accountId(): AccountId`, `approvedAt(): DateTimeImmutable`; no email, no password |
| `src/Boardly/IdentityAccess/Domain/Model/Account.php` | Verify approve() method and event production | `approve(DateTimeImmutable): AccountApprovalResult`; creates `AccountApproved($this->id, $approvedAt)` |
| `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountCommand.php` | Verify command shape | Carries only `email: string`; no actorId |
| `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php` | Verify outbox store call | Calls `$this->outbox->store([$domainResult->event()])` inside `transactional()`; account saved before outbox store |
| `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountResult.php` | Verify result fields | Exposes `accountId`, `email`, `status`, `approvedAt`; no password/hash/token |
| `src/Shared/Infrastructure/Outbox/OutboxMessageMapperInterface.php` | Mapper contract | `supports(OutboxRecord): bool`; `map(OutboxRecord): object` |
| `src/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistry.php` | Registry dispatch | Iterates tagged mappers; throws `InvalidArgumentException` on unsupported event type; no fallback |
| `src/Shared/Infrastructure/Outbox/OutboxRecord.php` | Outbox record shape | Fields: `id`, `eventId`, `eventType`, `aggregateType`, `aggregateId`, `payload`, `occurredAt`, `availableAt`, `publishedAt`, `attempts`, `lastError`, `createdAt` |
| `src/Shared/Infrastructure/Outbox/OutboxMessage.php` | Generic outbox message (not used by this handler) | `fromRecord()` exists; not the target of #41 |
| `src/Shared/Infrastructure/Outbox/OutboxPublisher.php` | Publisher flow | Uses `OutboxMessageMapperRegistry::map()` then `messageBus->dispatch()` on `event.bus` |
| `src/Shared/Application/Outbox/OutboxInterface.php` | Application port | `store(list<DomainEvent>): void` |
| `src/Shared/Application/Messaging/ProcessedMessageStoreInterface.php` | Idempotency port | `tryStart(eventId, handlerName): bool`; `markProcessed(eventId, handlerName): void` |
| `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializer.php` | Serializer — confirms payload shape | `eventType = 'identity_access.account_approved'`; payload = `{account_id, approved_at}`; no email/password |
| `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapper.php` | Mapper — already exists | Reads `account_id` + `approved_at`; validates consistency with `aggregateId`; maps to `AccountApprovedMessage`; throws `InvalidArgumentException` on bad payload |
| `src/Boardly/IdentityAccess/Infrastructure/Message/AccountApprovedMessage.php` | Message DTO — already exists | `outboxId`, `eventId`, `accountId`, `approvedAt`; no email/password |
| `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php` | Reference handler pattern | Uses `#[AsMessageHandler(bus: 'event.bus')]`; injects `LoggerInterface`, `TransactionalInterface`, `ProcessedMessageStoreInterface`; guard + log + markProcessed inside transaction |
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php` | Reference test pattern | Tests: accepts correct message type, no OutboxMessage dependency, transaction order, duplicate skip, rollback on failure, no email logged |
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php` | Mapper test — already exists | Covers: supports, rejects other event type, maps correctly, rejects unsafe fields, rejects missing/empty/non-string account_id, rejects missing/invalid approved_at, rejects aggregateId mismatch |
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializerTest.php` | Serializer test — already exists | Covers: supports, metadata, payload shape, no unsafe fields |
| `tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php` | Handler test — already exists | Covers: successful approval, missing account, invalid transition, transaction order, result safety |

---

## 4. Current AccountApproved Source State

`AccountApproved` is defined at:
`src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php`

It implements `App\Boardly\SharedKernel\Domain\Event\DomainEvent` (a marker interface).

Public API:
- `accountId(): AccountId`
- `approvedAt(): DateTimeImmutable`

The event carries no email, no password hash, no token, and no status field.

`Account::approve(DateTimeImmutable $approvedAt)` creates `AccountApproved($this->id, $approvedAt)` and
wraps it in `AccountApprovalResult`. `ApproveAccountHandler::__invoke()` calls
`$this->outbox->store([$domainResult->event()])` inside a `transactional()` block, immediately after
`$this->accounts->save($account)`. The outbox store is therefore guaranteed to run atomically with the
state change.

The serializer (`AccountApprovedOutboxEventSerializer`) produces exactly this payload:

```
{
    "account_id": "<uuid string>",
    "approved_at": "<ISO 8601 ATOM string>"
}
```

Event type string: `identity_access.account_approved`
Aggregate type: `identity_access.account`

No unsafe fields (email, password, hash, token) appear in the serialized payload or in `AccountApproved`
itself.

---

## 5. Current Transactional Outbox State

The outbox pipeline for the approve flow is fully wired except for the async message handler.

| Component | Status | File |
|-----------|--------|------|
| `AccountApproved` domain event | EXISTS — implements `DomainEvent` | `src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php` |
| `ApproveAccountHandler` stores event in outbox | EXISTS — calls `outbox->store()` inside transaction | `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php` |
| `AccountApprovedOutboxEventSerializer` | EXISTS — tagged as `boardly.outbox_event_serializer` | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializer.php` |
| `AccountApprovedOutboxMessageMapper` | EXISTS — tagged as `boardly.outbox_message_mapper` | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapper.php` |
| `AccountApprovedMessage` | EXISTS | `src/Boardly/IdentityAccess/Infrastructure/Message/AccountApprovedMessage.php` |
| `AccountApprovedMessage` routing in `messenger.yaml` | MISSING — not yet in routing table | `config/packages/messenger.yaml` |
| `AccountApprovedMessageHandler` | MISSING — to be created by #41 | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedMessageHandler.php` |

The `OutboxPublisher` dispatches the mapped message to `event.bus` (wired in `services.yaml` as
`$messageBus: '@event.bus'`). The `event.bus` has `allow_no_handlers: true`, so the missing handler
does not cause a runtime error today — but the log entry is never written.

---

## 6. Current Messenger State

From `config/packages/messenger.yaml`:

- Three buses: `command.bus` (default), `query.bus`, `event.bus`.
- `event.bus` has `allow_no_handlers: true`.
- Transport: `async` (RabbitMQ via `MESSENGER_TRANSPORT_DSN`); failure transport: `failed` (Doctrine).
- Current routing table has exactly one entry:
  `App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage: async`
- `AccountApprovedMessage` is not routed. Without routing, the handler is invoked synchronously by
  `event.bus->dispatch()` inside `OutboxPublisher::publish()` — messages never reach RabbitMQ and the
  async purpose is defeated.
- Test env overrides both `async` and `failed` transports to `in-memory://`.

From `config/services.yaml`:

- `OutboxMessageMapperInterface` implementations are auto-tagged `boardly.outbox_message_mapper` via
  `_instanceof`. The existing `AccountApprovedOutboxMessageMapper` is already auto-discovered.
- `AccountRegisteredOutboxMessageHandler` is not manually listed; it uses `#[AsMessageHandler(bus: 'event.bus')]`
  and relies on autoconfigure. The new handler should follow the same pattern.
- No manual `services.yaml` entry is needed for the new handler.

Required change to `messenger.yaml`: add:
```yaml
App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage: async
```

---

## 7. Required Implementation Shape

### `src/Boardly/IdentityAccess/Infrastructure/Message/AccountApprovedMessage.php`

Status: Already exists. No changes needed.

Responsibility: Pure transport DTO carrying the safe fields extracted from the outbox record.

Dependencies: None (no Symfony, no Doctrine, no domain model).

Fields: `outboxId: string`, `eventId: string`, `accountId: string`, `approvedAt: DateTimeImmutable` — all public readonly.

Layer appropriateness: Correct. Infrastructure message DTO. Domain-free. Transport-safe.

Risks: None. The class is already in place and tested.

---

### `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapper.php`

Status: Already exists. No changes needed.

Responsibility: Map an `OutboxRecord` with `eventType = 'identity_access.account_approved'` to an
`AccountApprovedMessage`. Validate payload. Reject unsupported event types.

Dependencies: `OutboxMessageMapperInterface`, `OutboxRecord`, `AccountApprovedMessage`.

Methods:
- `supports(OutboxRecord $record): bool` — returns `true` iff `eventType === 'identity_access.account_approved'`.
- `map(OutboxRecord $record): object` — extracts and validates `account_id` (non-empty string) and
  `approved_at` (valid datetime string); validates `aggregateId` consistency; returns `AccountApprovedMessage`.

Layer appropriateness: Correct. Infrastructure adapter. No business logic.

Risks: None. The class is already in place and fully tested.

---

### `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedMessageHandler.php`

Status: Does not exist. This is the only file that must be created by #41.

Responsibility: Consume `AccountApprovedMessage` from `event.bus`, execute a processed-message guard,
write exactly one structured `info`-level log entry, and mark the message as processed. This is a
diagnostic consumer — no repository calls, no aggregate mutation, no business state change.

Dependencies:
- `Psr\Log\LoggerInterface` — for structured log output.
- `App\Shared\Application\Transaction\TransactionalInterface` — to wrap the guard and the log call in
  a DB transaction so the guard and markProcessed commit atomically.
- `App\Shared\Application\Messaging\ProcessedMessageStoreInterface` — for at-least-once idempotency.
- `Symfony\Component\Messenger\Attribute\AsMessageHandler` — transport binding attribute.

Fields/methods likely needed:
- Constructor accepting all three dependencies.
- `__invoke(AccountApprovedMessage $message): void` — the Messenger handler entry point.
- Inside `__invoke`: wrap in `$this->transactional->transactional(function() use ($message) { ... })`.
  - Call `$this->processedMessages->tryStart($message->eventId, self::class)` — return early if false.
  - Call `$this->logger->info('IdentityAccess account approved.', [...])` with the four safe context keys.
  - Call `$this->processedMessages->markProcessed($message->eventId, self::class)`.

`#[AsMessageHandler(bus: 'event.bus')]` attribute must be applied at class level.

Class should be `final readonly`.

Layer appropriateness: Correct. Infrastructure handler in the owning bounded context. Does not leak
domain objects, does not own business invariants, does not call application commands.

Risks:
- If `messenger.yaml` routing is not updated to route `AccountApprovedMessage` to `async`, the handler
  runs synchronously inside `OutboxPublisher::publish()`, skipping RabbitMQ.
- The processed-message guard requires the `processed_messages` table. Since
  `AccountRegisteredOutboxMessageHandler` already uses the same guard, this table is assumed present.
- At-least-once delivery is safe: `tryStart` returns false on duplicate delivery, preventing duplicate
  log entries.

---

## 8. Mapper Contract Analysis

`AccountApprovedOutboxMessageMapper` (already implemented) satisfies the following contract:

- **Supports only `identity_access.account_approved`**: `supports()` returns `true` only when
  `$record->eventType === 'identity_access.account_approved'`. Any other event type returns `false`.

- **Rejects all other event types**: Confirmed by test `testDoesNotSupportAnotherEventType`.

- **Validates required payload fields**:
  - `account_id` — must be present, must be a non-empty string. Missing, empty, or non-string throws
    `InvalidArgumentException` with message containing `payload field "account_id" must be a non-empty string`.
  - `approved_at` — must be present, must be a valid datetime string. Missing, empty, non-string, or
    unparseable throws `InvalidArgumentException` with message containing
    `payload field "approved_at" must be a valid date/time string`.

- **Validates aggregate id consistency**: if `$record->aggregateId` is non-null, it must equal the
  `account_id` from the payload. Mismatch throws `InvalidArgumentException`.

- **Maps into `AccountApprovedMessage`**: constructs with `outboxId = $record->id`,
  `eventId = $record->eventId`, `accountId`, `approvedAt`.

- **Fails loudly on malformed payload**: all validation errors throw `InvalidArgumentException`
  immediately. The registry propagates these to `OutboxPublisher`, which calls `outbox->recordFailure()`
  so the record is retried rather than silently dropped.

- **Does not silently drop malformed events**: no `try/catch` inside the mapper methods. Exceptions
  propagate and are recorded as outbox failures for retry and inspection.

---

## 9. Handler Contract Analysis

`AccountApprovedMessageHandler` (to be created) must satisfy the following:

- **Uses `Psr\Log\LoggerInterface`**: injected as constructor dependency; called once per successful
  handling cycle.

- **Writes exactly one info-level structured log entry**: `$this->logger->info(...)` is called after
  the `tryStart` guard passes, before `markProcessed`. If `tryStart` returns false (duplicate), no
  log entry is written.

- **Message**: `'IdentityAccess account approved.'`

- **Context fields**:
  ```php
  [
      'event_id'    => $message->eventId,
      'outbox_id'   => $message->outboxId,
      'account_id'  => $message->accountId,
      'approved_at' => $message->approvedAt->format(DATE_ATOM),
  ]
  ```

- **No email/password/hash/token/cookie/secret**: `AccountApprovedMessage` does not carry these fields.
  The handler must not introduce any additional data source that could expose them.

- **No repository calls unless required**: the log handler is a pure diagnostic consumer. No
  `AccountRepositoryInterface` dependency is needed. The only data source is the message itself.

- **No account state mutation**: the handler must not call any command or modify any aggregate.

- **Safe under at-least-once delivery**: the `tryStart(eventId, self::class)` guard ensures the log
  entry and `markProcessed` are only executed once per `(event_id, handler_name)` pair even if the
  same message is delivered multiple times.

- **Transaction scope**: `tryStart`, the log call, and `markProcessed` must all execute inside a single
  call to `TransactionalInterface::transactional()`, consistent with the
  `AccountRegisteredOutboxMessageHandler` pattern and `async-message-handler-rules.md`.

---

## 10. Sensitive Data Analysis

The following fields are sensitive and must NEVER be logged in any handler, mapper, or serializer
involved in the approve flow:

| Field | Source | Status |
|-------|--------|--------|
| `email` | `Account` aggregate | Not in `AccountApproved`; not in serialized payload; not in `AccountApprovedMessage`. Safe. |
| `password` (plain) | Never stored | Not in any event or message. Safe. |
| `passwordHash` | `Account` aggregate | Not in `AccountApproved`; not in serialized payload; not in `AccountApprovedMessage`. Safe. |
| `access_token` | `JwtAccessTokenService` | Not relevant to approval event. Safe. |
| `refresh_token` (plain) | `SecureRefreshTokenGenerator` | Not relevant to approval event. Safe. |
| `refresh_token_hash` | `HmacRefreshTokenHasher` | Not relevant to approval event. Safe. |
| `cookie` values | HTTP layer | Not present in domain or infrastructure events. Safe. |
| `status` | `AccountStatus` value object | Not in `AccountApprovedMessage`; filtered out in mapper test. Excluded by design. |

The `AccountApprovedOutboxEventSerializer` produces only `account_id` and `approved_at` in the payload.
The `AccountApprovedOutboxMessageMapper` reads only those two fields and maps them into
`AccountApprovedMessage`. The mapper test `testDoesNotExposeCredentialsTokensOrUnsafeData` explicitly
asserts that even if extra unsafe fields are present in the payload, they are not transferred into the
message object.

The handler must not add any new data source and must not log any field beyond the four specified safe
context keys: `event_id`, `outbox_id`, `account_id`, `approved_at`.

---

## 11. Test Inventory and Test Plan Inputs

### Existing tests relevant to #41

| Test file | Coverage |
|-----------|----------|
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializerTest.php` | Serializer: supports, metadata, payload shape, no unsafe fields |
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php` | Mapper: supports, rejects other type, maps correctly, no unsafe fields, missing/empty/non-string account_id, missing/invalid approved_at, aggregateId mismatch |
| `tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php` | Application handler: approval success, missing account, invalid transition, transaction order, result safety |
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php` | Reference for the exact handler pattern to replicate |

### Missing test (to be created)

| Test file | Cases to cover |
|-----------|----------------|
| `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedMessageHandlerTest.php` | 1. Handler accepts `AccountApprovedMessage` (not `OutboxMessage`) |
| | 2. Handler source does not reference `OutboxMessage`, `event_type`, `eventType`, or `identity_access.account_approved` |
| | 3. Happy path: `tryStart` then `log.info` then `markProcessed` then transaction commit, in correct order |
| | 4. Duplicate delivery skipped: `tryStart` returns false, no log, no `markProcessed`, transaction still commits |
| | 5. Rollback on side-effect failure: if logger throws, `markProcessed` is not called, transaction rolls back |
| | 6. Log message is exactly `'IdentityAccess account approved.'` |
| | 7. Log context has exactly `event_id`, `outbox_id`, `account_id`, `approved_at` (ATOM format) |
| | 8. No email is logged: `email` not present in `json_encode($logger->records)` |
| | 9. `tryStart` and `markProcessed` called with `($message->eventId, AccountApprovedMessageHandler::class)` |
| | 10. `tryStart` and `markProcessed` run inside the active transaction |

Test doubles needed: `RecordingTransactional`, `FakeProcessedMessageStore`, `RecordingLogger` — structurally
identical to those in `AccountRegisteredOutboxMessageHandlerTest.php`.

---

## 12. Risks / Blockers / Open Questions

### Blockers

None. All prior blockers identified in MemPalace memory have already been resolved in the codebase:

- `AccountApproved` implements `DomainEvent` — RESOLVED.
- `ApproveAccountHandler` stores the event through `OutboxInterface` — RESOLVED.
- `OutboxMessageMapperInterface` exists and is tagged — RESOLVED.
- `AccountApprovedOutboxMessageMapper` and `AccountApprovedMessage` both exist — RESOLVED.
- Payload shape is fully known and tested — RESOLVED.

### Risks

1. **Missing `messenger.yaml` routing entry**: without `AccountApprovedMessage: async`, the
   `OutboxPublisher` dispatches the message synchronously on `event.bus`. The handler runs inline
   during the `publish` command rather than through RabbitMQ. This must be fixed in the same PR.

2. **Handler placement**: the issue prompt suggests
   `src/Boardly/IdentityAccess/Infrastructure/Messaging/Handler/`. The existing analogous handler
   (`AccountRegisteredOutboxMessageHandler`) lives under `src/Boardly/IdentityAccess/Infrastructure/Outbox/`.
   For consistency with the current codebase, `Infrastructure/Outbox/` is preferred. If
   `Messaging/Handler/` is introduced, the existing handler should be migrated in the same PR.

3. **`processed_messages` table**: assumed present because `AccountRegisteredOutboxMessageHandler`
   already uses the same `ProcessedMessageStoreInterface`. Pre-existing operational concern, not new.

### Open Questions

- Should the handler be named `AccountApprovedMessageHandler` (issue prompt) or
  `AccountApprovedOutboxMessageHandler` (existing pattern)?
- Should it live in `Infrastructure/Outbox/` (current convention) or `Infrastructure/Messaging/Handler/`?

---

## 13. Implementation Readiness

Ready for planning

All domain, application, serializer, mapper, and message DTO prerequisites are in place. The deliverables
for #41 are limited to three items:

1. Create `AccountApprovedMessageHandler` (or `AccountApprovedOutboxMessageHandler`) under
   `src/Boardly/IdentityAccess/Infrastructure/Outbox/`.
2. Add routing entry for `AccountApprovedMessage: async` in `config/packages/messenger.yaml`.
3. Create the corresponding handler test under
   `tests/Boardly/IdentityAccess/Infrastructure/Outbox/`.

No other source file modifications are required.

---

## 14. Suggested Next Planning Focus

1. **Confirm naming convention**: decide between `AccountApprovedOutboxMessageHandler` (matches existing
   registered handler pattern) vs `AccountApprovedMessageHandler` (matches issue prompt). Standardize
   for all future handlers.

2. **Confirm placement**: `Infrastructure/Outbox/` (current convention) vs `Infrastructure/Messaging/Handler/`
   (issue prompt suggestion). If `Messaging/Handler/` is chosen, migrate
   `AccountRegisteredOutboxMessageHandler` in the same PR to avoid two divergent conventions.

3. **Write the handler**: follow `AccountRegisteredOutboxMessageHandler` exactly — same constructor
   shape, same transaction guard structure, same `#[AsMessageHandler(bus: 'event.bus')]` attribute.
   Replace the log message with `'IdentityAccess account approved.'` and context with the four approved
   fields.

4. **Update `messenger.yaml`**: add `AccountApprovedMessage: async` routing in the same PR as the handler.

5. **Write the test**: follow `AccountRegisteredOutboxMessageHandlerTest` as the model. Reuse the same
   test double shapes (`RecordingTransactional`, `FakeProcessedMessageStore`, `RecordingLogger`) — either
   copy into the new test file or extract to a shared test support class.

6. **Integration verification**: run `bin/console messenger:consume async --limit=1` against a seeded
   outbox record with `event_type = 'identity_access.account_approved'` and confirm the log entry
   appears in the expected format.
