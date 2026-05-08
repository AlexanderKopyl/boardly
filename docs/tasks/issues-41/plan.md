# Issue #41 Plan: Add Async Log Handler for Approved IdentityAccess Accounts

## MemPalace Summary

MemPalace was queried for three areas before writing this plan:

**1. Async message handler rules** (from `async-message-handler-rules.md`)
- Handlers must wrap `tryStart + side effects + markProcessed` in a single DB transaction.
- Handlers must not assume exactly-once delivery; idempotency is mandatory.
- Handlers must not mutate aggregate state, dispatch core commands, or enforce business permission checks.
- Log/diagnostic handlers are an explicitly supported use case.
- Confirmed: rules align with the plan below.

**2. Messenger routing conventions** (from `application-bus-rules.md` + prior analysis)
- Routing must be explicit per concrete message class.
- No broad `App\Boardly\*: async` routing.
- `command.bus` and `query.bus` remain synchronous.
- `event.bus` is used for outbox-dispatched messages; `OutboxPublisher` is wired to it.
- Confirmed: `AccountApprovedMessage: async` follows this convention exactly.

**3. IdentityAccess AccountApproved flow** (from stale prior analysis in MemPalace)
- MemPalace held an outdated memory (from issue #36/39) stating that `AccountApproved` was NOT a `DomainEvent`, and that `ApproveAccountHandler`, `AccountApprovedOutboxEventSerializer`, `AccountApprovedOutboxMessageMapper`, and `AccountApprovedMessage` were all missing.
- **Current code is authoritative.** All those gaps have been resolved. The memory is stale and is treated as superceded by the repository state documented in `analysis.md`.

---

## 1. Goal

Complete the async observability pipeline for the account approval use case by adding Messenger routing and a structured log handler.

**End-to-end flow:**

```
Account::approve()
  -> AccountApproved domain event
  -> ApproveAccountHandler stores via OutboxInterface (transactional with account save)
  -> OutboxPublisher reads unpublished outbox record
  -> AccountApprovedOutboxEventSerializer -> OutboxRecord (eventType: identity_access.account_approved)
  -> OutboxMessageMapperRegistry -> AccountApprovedOutboxMessageMapper
  -> AccountApprovedMessage dispatched to event.bus
  -> Messenger routes AccountApprovedMessage to async (RabbitMQ)
  -> AccountApprovedOutboxMessageHandler consumes from event.bus
  -> tryStart idempotency guard
  -> logger.info("IdentityAccess account approved.", {event_id, outbox_id, account_id, approved_at})
  -> markProcessed
```

The handler is a **diagnostic consumer only**: no repository calls, no aggregate mutation, no command dispatch.

---

## 2. Current State Summary

### Already exists — no changes needed

| Component | File |
|-----------|------|
| `AccountApproved` domain event (implements `DomainEvent`) | `src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php` |
| `Account::approve()` produces `AccountApproved` | `src/Boardly/IdentityAccess/Domain/Model/Account.php` |
| `ApproveAccountHandler` stores event via `OutboxInterface` | `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandler.php` |
| `AccountApprovedOutboxEventSerializer` | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializer.php` |
| `AccountApprovedOutboxMessageMapper` | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapper.php` |
| `AccountApprovedMessage` | `src/Boardly/IdentityAccess/Infrastructure/Message/AccountApprovedMessage.php` |
| Mapper tests | `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php` |
| Serializer tests | `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxEventSerializerTest.php` |
| Application handler tests | `tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php` |

### Missing — deliverables for issue #41

| Component | File |
|-----------|------|
| Messenger routing for `AccountApprovedMessage` | `config/packages/messenger.yaml` |
| `AccountApprovedOutboxMessageHandler` | `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandler.php` |
| Handler tests | `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandlerTest.php` |

---

## 3. Naming and Placement Decision

**Chosen name:** `AccountApprovedOutboxMessageHandler`
**Chosen path:** `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandler.php`

**Rationale:**
- Mirrors the existing `AccountRegisteredOutboxMessageHandler` exactly.
- Keeps the outbox consumer co-located with the outbox mapper and serializer for the same bounded context.
- Avoids introducing a parallel `Infrastructure/Messaging/Handler/` directory that would split conventions between two patterns.
- Any future handler for a different event type in IdentityAccess follows the same `Infrastructure/Outbox/` placement until a deliberate ADR changes the convention.

**Explicitly rejected for this issue:** introducing `Infrastructure/Messaging/Handler/` as a handler location. That change would also require migrating `AccountRegisteredOutboxMessageHandler`, which is out of scope for #41.

---

## 4. Implementation Sequence

- [ ] Step 1: Add Messenger routing for `AccountApprovedMessage`
- [ ] Step 2: Create `AccountApprovedOutboxMessageHandler`
- [ ] Step 3: Add `AccountApprovedOutboxMessageHandlerTest`
- [ ] Step 4: Run final QA/verification

---

## 5. Step 1 — Messenger Routing

**File:** `config/packages/messenger.yaml`

**Change:** Add one routing entry under `framework.messenger.routing`:

```yaml
App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage: async
```

**Rules:**
- Keep the existing `AccountRegisteredMessage: async` entry unchanged.
- Do not change buses, transports, retry strategies, or failure transport.
- Do not route by namespace prefix (`App\Boardly\*`).
- Only explicit, per-class routing entries.

**Verification:**

```bash
php bin/console lint:yaml config/packages/messenger.yaml
php bin/console debug:messenger | grep AccountApprovedMessage
```

Expected: `AccountApprovedMessage` appears in the routing table pointing to the `async` transport.

---

## 6. Step 2 — Handler

**File:** `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandler.php`

**Class shape:**

```
final readonly class AccountApprovedOutboxMessageHandler
```

**Attribute:** `#[AsMessageHandler(bus: 'event.bus')]`

**Constructor dependencies:**
- `Psr\Log\LoggerInterface $logger`
- `App\Shared\Application\Transaction\TransactionalInterface $transactional`
- `App\Shared\Application\Messaging\ProcessedMessageStoreInterface $processedMessages`

**Method:** `__invoke(AccountApprovedMessage $message): void`

**Pseudo-flow:**

```php
$this->transactional->transactional(function () use ($message): void {
    if (!$this->processedMessages->tryStart($message->eventId, self::class)) {
        return;
    }

    $this->logger->info('IdentityAccess account approved.', [
        'event_id'    => $message->eventId,
        'outbox_id'   => $message->outboxId,
        'account_id'  => $message->accountId,
        'approved_at' => $message->approvedAt->format(DATE_ATOM),
    ]);

    $this->processedMessages->markProcessed($message->eventId, self::class);
});
```

**Rules:**
- No repository injection.
- No account state mutation.
- No command dispatch.
- No additional data sources beyond `$message`.
- Never log: `email`, `password`, `passwordHash`, `accessToken`, `refreshToken`, `refresh_token`, `cookie`, `secret`.
- No dependency on `OutboxMessage` or `OutboxRecord`.
- No dependency on `event_type` / `eventType` strings.
- No `services.yaml` entry needed — autoconfigure handles `#[AsMessageHandler]`.

---

## 7. Step 3 — Tests

**File:** `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandlerTest.php`

**Model:** `tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php`

**Test doubles needed:**
- `RecordingTransactional` — captures transaction lifecycle (begin/commit/rollback)
- `FakeProcessedMessageStore` — controls `tryStart` return value, records `markProcessed` calls
- `RecordingLogger` — records all `info()` calls with message and context

**Test cases:**

| # | Name | Asserts |
|---|------|---------|
| 1 | Handler typehints `AccountApprovedMessage`, not `OutboxMessage` | Reflection on `__invoke` parameter type |
| 2 | Source does not reference `OutboxMessage`, `event_type`, `eventType`, `identity_access.account_approved` | `file_get_contents` + `assertStringNotContainsString` on handler source |
| 3 | Happy path — correct execution order | Transaction begun; `tryStart` called; `logger.info` called; `markProcessed` called; transaction committed — in that order |
| 4 | Duplicate delivery — `tryStart` returns false | `tryStart` returns false; no log entry; no `markProcessed` call; transaction still commits |
| 5 | Logger failure causes rollback | Logger throws; `markProcessed` not called; transaction rolled back |
| 6 | Log message is exactly `'IdentityAccess account approved.'` | Assert on `$logger->records[0]['message']` |
| 7 | Log context contains exactly `event_id`, `outbox_id`, `account_id`, `approved_at` | Assert exact key set on `$logger->records[0]['context']` |
| 8 | `approved_at` is formatted as `DATE_ATOM` | Assert `$logger->records[0]['context']['approved_at']` matches ATOM format |
| 9 | `tryStart` and `markProcessed` called with `($message->eventId, AccountApprovedOutboxMessageHandler::class)` | Assert exact arguments on both calls |
| 10 | No unsafe fields in logged context | Assert `email`, `password`, `passwordHash`, `accessToken`, `refreshToken`, `refresh_token`, `cookie`, `secret` are not present in `json_encode($logger->records)` |

---

## 8. Step 4 — Final QA

```bash
php bin/console lint:yaml config/packages/messenger.yaml
php bin/console debug:messenger | grep AccountApprovedMessage
vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandlerTest.php
vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageMapperTest.php
vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountHandlerTest.php
composer phpstan
composer qa
```

All commands must pass with no failures before the PR is submitted.

---

## 9. Acceptance Criteria

- [ ] `AccountApprovedMessage` is routed to the `async` transport in `messenger.yaml`
- [ ] `AccountApprovedOutboxMessageHandler` is registered on `event.bus` via `#[AsMessageHandler(bus: 'event.bus')]`
- [ ] Handler consumes `AccountApprovedMessage` (not `OutboxMessage`)
- [ ] Handler logs exactly one `info`-level structured entry per unique delivery
- [ ] Log message is exactly `'IdentityAccess account approved.'`
- [ ] Log context contains exactly: `event_id`, `outbox_id`, `account_id`, `approved_at`
- [ ] `approved_at` is formatted as `DATE_ATOM`
- [ ] Duplicate delivery is idempotently skipped (no log, no `markProcessed`, transaction still commits)
- [ ] Unsafe fields (`email`, `password`, `passwordHash`, `accessToken`, `refreshToken`, `cookie`, `secret`) are never logged
- [ ] No domain or application behavior is changed
- [ ] `AccountApprovedOutboxMessageMapperTest` still passes
- [ ] `ApproveAccountHandlerTest` still passes
- [ ] `phpstan` and `composer qa` pass

---

## 10. Non-Goals

The following are explicitly out of scope for issue #41:

- No audit subsystem — the log entry is a diagnostic observation, not an auditable record.
- No email notification — sending approval emails is a separate feature.
- No account state mutation — the handler reads the message only; no aggregate is loaded or saved.
- No repository calls from the log handler — `AccountRepositoryInterface` must not be injected.
- No new domain event — `AccountApproved` already exists and is unchanged.
- No mapper rewrite — `AccountApprovedOutboxMessageMapper` is complete and tested.
- No serializer rewrite — `AccountApprovedOutboxEventSerializer` is complete and tested.
- No broad Messenger routing — only `AccountApprovedMessage: async` is added.
- No migration of `AccountRegisteredOutboxMessageHandler` to a different directory.
- No introduction of `Infrastructure/Messaging/Handler/` as a handler location.
