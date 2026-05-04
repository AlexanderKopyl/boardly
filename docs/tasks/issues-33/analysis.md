# Issue 33 Analysis: Refactor Outbox Publishing to Concrete Event Messages

## Reading Scope

This analysis is based on the requested repository reading scope:

- `AGENTS.md`
- `docs/architecture/project-architecture-rules.md`
- `docs/architecture/application-bus-rules.md`
- `docs/architecture/async-message-handler-rules.md`
- `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`
- current outbox publisher, outbox record/message storage, AccountRegistered outbox handler, processed-message store, Messenger/service wiring, and related tests

Specialist review was requested and used for Symfony architecture, async messaging, DDD/domain modeling, testing/security, and operational documentation. Sequential decomposition was used to confirm dependency direction, implementation order, and failure-behavior trade-offs.

No ADR gap was found. ADR-0003 already accepts the transactional outbox, post-commit publisher, retryable publication, and idempotent async consumers. `docs/architecture/async-message-handler-rules.md` already identifies generic `OutboxMessage` consumers as acceptable for small diagnostics and recommends explicit message classes once real consumer boundaries grow. This issue is an implementation refinement under those accepted rules, not a new architecture decision.

## 1. Current State

The current publishing flow is:

```text
RegisterAccountHandler
-> stores AccountRegistered through DoctrineOutbox
-> outbox_messages row
-> OutboxPublisher::publish()
-> OutboxMessage::fromRecord($record)
-> event.bus dispatch
-> Messenger async transport
-> AccountRegisteredOutboxMessageHandler::__invoke(OutboxMessage)
-> handler filters by event_type
```

`src/Shared/Infrastructure/Outbox/OutboxPublisher.php` loads unpublished records through `DoctrineOutbox::loadUnpublished()`. For each record, it dispatches:

```php
$this->messageBus->dispatch(OutboxMessage::fromRecord($record));
```

On dispatch success it calls `DoctrineOutbox::markPublished($record->id, $now)`. On dispatch failure it calls `DoctrineOutbox::recordFailure($record->id, $exception->getMessage(), $now->modify('+60 seconds'))`, increments the failed count, and continues with later records.

`src/Shared/Infrastructure/Outbox/OutboxMessage.php` is a generic transport-level wrapper containing:

- `outboxId`
- `eventId`
- `eventType`
- `aggregateType`
- `aggregateId`
- `payload`
- `occurredAt`

`src/Shared/Infrastructure/Outbox/OutboxRecord.php` represents the persisted outbox row loaded from `outbox_messages`, including retry state: `availableAt`, `publishedAt`, `attempts`, `lastError`, and `createdAt`.

`src/Shared/Infrastructure/Outbox/DoctrineOutbox.php` currently owns:

- storing serialized domain events;
- loading unpublished available records;
- marking records published;
- recording publication failures.

The current AccountRegistered consumer is `src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php`. It accepts the generic wrapper:

```php
public function __invoke(OutboxMessage $message): void
```

It then filters internally:

```php
if (self::EVENT_TYPE !== $message->eventType) {
    return;
}
```

Only after this filter does it enter the transaction and execute the idempotency guard.

Current Messenger routing in `config/packages/messenger.yaml` routes the generic message:

```yaml
App\Shared\Infrastructure\Outbox\OutboxMessage: async
```

Messenger buses are configured as:

- `command.bus`
- `query.bus`
- `event.bus`

`command.bus` is the default bus. `event.bus` allows no handlers. The outbox publisher is wired in `config/services.yaml` with:

```yaml
App\Shared\Infrastructure\Outbox\OutboxPublisher:
    arguments:
        $messageBus: '@event.bus'
```

The command and query bus adapters are explicitly wired to `command.bus` and `query.bus`; they must remain synchronous for application commands/queries unless a separate design says otherwise.

Current idempotency is based on `src/Shared/Application/Messaging/ProcessedMessageStoreInterface.php`:

```php
public function tryStart(string $eventId, string $handlerName): bool;
public function markProcessed(string $eventId, string $handlerName): void;
```

`src/Shared/Infrastructure/Messaging/DoctrineProcessedMessageStore.php` implements this by inserting into `processed_messages` with `event_id` and `handler_name`. A unique constraint violation returns `false` from `tryStart()`. `markProcessed()` updates the same pair to `processed`.

The current AccountRegistered handler uses:

```php
$this->processedMessages->tryStart($message->eventId, self::class)
$this->processedMessages->markProcessed($message->eventId, self::class)
```

inside `TransactionalInterface::transactional(...)`.

Existing tests currently cover:

- `OutboxPublisherTest`
  - dispatching generic `OutboxMessage`;
  - marking published after dispatch success;
  - recording failure after dispatch exception;
  - continuing after one record fails;
  - zero limit behavior;
  - wrapper field mapping from persisted record;
  - operation order: dispatch before mark published.
- `PublishOutboxCommandTest`
  - default/custom limit;
  - output counts;
  - invalid limit handling;
  - dispatching generic `OutboxMessage` through the command path.
- `DoctrineOutboxTest`
  - storing one row per event;
  - DBAL types and no transaction control in the adapter.
- `DoctrineOutboxIntegrationTest`
  - storing AccountRegistered records;
  - loading unpublished available records in deterministic order;
  - respecting limits;
  - marking published;
  - recording failure and adjusting retry availability.
- `OutboxEventSerializerRegistryTest`
  - serializer delegation;
  - clear unsupported-domain-event exception;
  - no dependency on concrete AccountRegistered classes in the registry.
- `AccountRegisteredOutboxEventSerializerTest`
  - supports AccountRegistered;
  - event metadata;
  - payload shape;
  - absence of password/token/security data.

No existing tests were found for `AccountRegisteredOutboxMessageHandler` idempotency behavior or `DoctrineProcessedMessageStore`.

## 2. Problem

Generic `OutboxMessage` dispatching does not scale.

Every async handler that consumes `OutboxMessage` becomes eligible to receive every outbox event routed through Messenger. Each handler must inspect `eventType` and return early for events it does not own. That makes handlers do routing work that Messenger is designed to do.

The handler signature also lies about the consumer boundary:

```php
__invoke(OutboxMessage $message)
```

does not express that the handler consumes only `identity_access.account_registered`. The real contract is hidden in an internal conditional.

As more bounded contexts and side effects are added, this pattern creates several problems:

- all `OutboxMessage` handlers receive all outbox messages;
- each handler must filter internally by `event_type`;
- handler signatures do not express what they actually consume;
- adding new contexts increases filtering, coupling, and accidental handling risk;
- Shared generic wrappers leak into context-specific consumers;
- Messenger's normal message-to-handler routing model is weakened;
- routing mistakes are harder to detect because handlers silently skip messages.

This conflicts with the Open/Closed guidance in `docs/architecture/project-architecture-rules.md`: Shared mechanisms should expose extension points, while bounded contexts should provide their own implementations instead of growing central conditionals or generic filtering.

## 3. Target Design

Introduce a publishing-side mapping extension point:

```text
OutboxRecord
-> OutboxMessageMapperRegistry
-> concrete internal message object
-> Messenger dispatch
-> concrete handler
```

Recommended Shared infrastructure additions:

```text
src/Shared/Infrastructure/Outbox/OutboxMessageMapperInterface.php
src/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistry.php
```

Recommended interface shape:

```php
interface OutboxMessageMapperInterface
{
    public function supports(OutboxRecord $record): bool;

    public function map(OutboxRecord $record): object;
}
```

The registry should mirror the existing serializer registry pattern:

- accept `iterable<OutboxMessageMapperInterface>`;
- iterate in order;
- delegate to the first mapper whose `supports()` returns `true`;
- throw a clear exception when no mapper supports the record.

Recommended IdentityAccess additions:

```text
src/Boardly/IdentityAccess/Infrastructure/Message/AccountRegisteredMessage.php
src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapper.php
```

The user-requested location for the concrete message is `Infrastructure/Message`; use that path unless implementation inspection finds a stronger local convention. The handler may remain in `Infrastructure/Outbox` initially to minimize churn, or be renamed later if a clearer Messenger-specific folder convention emerges. The plan should avoid unrelated moves.

`AccountRegisteredOutboxMessageMapper` should:

- support only `identity_access.account_registered`;
- validate required payload fields;
- map persisted outbox data to `AccountRegisteredMessage`;
- ignore fields not needed by the concrete consumer;
- not depend on the AccountRegistered domain event object, because publishing works from persisted records after commit.

`AccountRegisteredMessage` should include a stable idempotency key:

- `eventId`

It should also include only consumer-needed fields. For the current diagnostic handler, recommended fields are:

- `outboxId` if the handler logs the source outbox row;
- `eventId`;
- `accountId`;
- `registeredAt`;
- `isSystemAdmin` if diagnostic logging keeps it.

Default recommendation: avoid putting raw `email` into `AccountRegisteredMessage` unless a real current consumer needs it. Email is PII and can be loaded by `accountId` for many future consumers. Existing outbox storage may still contain email because changing storage payload is outside this issue's non-goals; the new mapper can ignore it. If email is kept in the concrete message for compatibility with diagnostic logging, the handler must continue logging only a hash, not raw email.

Refactor `OutboxPublisher` to dispatch the mapped concrete message:

```text
$message = $mapperRegistry->map($record)
$eventBus->dispatch($message)
markPublished only after dispatch succeeds
recordFailure if mapping or dispatch fails
```

Refactor `AccountRegisteredOutboxMessageHandler` so its signature is:

```php
public function __invoke(AccountRegisteredMessage $message): void
```

Remove the internal `event_type` filter. Messenger message type and routing should become the consumer boundary.

Register mappers with `_instanceof` and a tagged iterator:

```yaml
App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface:
    tags:
        - { name: boardly.outbox_message_mapper }
```

Wire the registry with:

```yaml
App\Shared\Infrastructure\Outbox\OutboxMessageMapperRegistry:
    arguments:
        $mappers: !tagged_iterator boardly.outbox_message_mapper
```

Route the concrete message explicitly:

```yaml
App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage: async
```

Do not add broad routing such as:

```yaml
App\Boardly\*: async
```

## 4. Dependency Direction

Correct dependency direction:

```text
Shared OutboxPublisher
-> Shared OutboxMessageMapperRegistry
-> Shared OutboxMessageMapperInterface
```

`OutboxPublisher` should know only:

- `DoctrineOutbox`;
- `OutboxRecord`;
- `OutboxMessageMapperRegistry`;
- `MessageBusInterface`;
- publish/failure result bookkeeping.

The Shared registry should know only:

- `OutboxMessageMapperInterface`;
- `OutboxRecord`;
- generic `object` return values.

Shared infrastructure must not depend on:

- `AccountRegisteredMessage`;
- `AccountRegistered`;
- IdentityAccess event type constants;
- IdentityAccess payload semantics.

IdentityAccess mapper should know:

- event type `identity_access.account_registered`;
- expected persisted payload shape;
- concrete `AccountRegisteredMessage` constructor;
- field validation for that message.

IdentityAccess handler should know:

- `AccountRegisteredMessage`;
- `ProcessedMessageStoreInterface`;
- `TransactionalInterface`;
- any side-effect/logging collaborators it uses.

This keeps the technical outbox mechanism reusable and keeps Boardly-specific business/message knowledge in the owning bounded context.

## 5. Idempotency

Idempotency should remain based on:

```text
event_id + handler class
```

The concrete message must carry `eventId`. The AccountRegistered handler should continue using:

```php
$this->processedMessages->tryStart($message->eventId, self::class)
$this->processedMessages->markProcessed($message->eventId, self::class)
```

inside one `TransactionalInterface::transactional(...)` callback.

Duplicate delivery should be skipped when `tryStart()` returns `false`. The handler class remains part of the processed-message identity so future audit, notification, search, or reporting handlers can process the same event independently.

`markProcessed()` must remain after the side effect succeeds. It must not be moved before the side effect.

Important risk: current `tryStart()` semantics insert a `started` row and later duplicate attempts return `false`, even if the handler failed after `tryStart()` but before `markProcessed()`. This issue does not need to redesign processed-message recovery, but the implementation should add tests that make current behavior explicit and should avoid adding new side effects that make stale `started` rows dangerous without a recovery strategy.

## 6. Messenger Routing

Required routing change:

- remove or stop relying on `App\Shared\Infrastructure\Outbox\OutboxMessage: async` once publishing no longer dispatches it;
- add explicit routing for `App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage: async`;
- keep `command.bus` and `query.bus` synchronous;
- keep application commands off async transports unless a future design explicitly changes that.

The outbox publisher can continue dispatching through `event.bus`, but routing should be by concrete message class. The handler should remain registered on `event.bus`.

Do not add broad routing such as:

```yaml
App\Boardly\*: async
```

or any namespace-wide async routing that could accidentally make application commands asynchronous.

Verification should include:

```text
php bin/console debug:config framework messenger
php bin/console debug:messenger
```

and an architecture search proving no broad async routing was introduced.

## 7. Risks and Decisions

### Unsupported OutboxRecord

If no mapper supports an `OutboxRecord`, the registry should throw a clear exception such as:

```text
Unsupported outbox record event type "..."
```

The publisher should treat this as a publication failure for that record.

### Bubble or Record Failure

Default recommendation: `OutboxPublisher` should catch mapping failures and dispatch failures in the same per-record `try/catch`, call `recordFailure(...)`, not mark the record published, increment the failed count, and continue to later records.

Letting mapping exceptions bubble would make the console command fail the whole batch and can starve later valid records. Silently skipping unsupported records would hide configuration or payload problems.

### Mapping Failures

Mapping failures should call `recordFailure(...)`.

Examples:

- no mapper supports the event type;
- missing required payload field;
- invalid payload field type;
- invalid date format;
- inconsistent account id fields if the mapper chooses to validate `aggregateId` against `payload.account_id`.

The current schema has no poison/quarantine state. Repeated mapping failures will retry via `attempts`, `last_error`, and `available_at`. A future issue can add max-attempt handling or an inspection/retry command if needed.

### Generic OutboxMessage Class

`OutboxMessage` can remain temporarily if tests, old diagnostics, or future infrastructure-level consumers still need it. After refactor, it should no longer be used for normal publishing.

Recommended implementation decision:

- remove Messenger routing for `OutboxMessage` if no publisher path dispatches it;
- delete `OutboxMessage` only if architecture searches prove it is unused outside tests and no backward-compatibility need remains;
- otherwise keep it as an unused/legacy infrastructure DTO without routing.

### Email in AccountRegisteredMessage

Default recommendation: do not include raw email in `AccountRegisteredMessage` unless a real current consumer needs it.

Reasons:

- email is PII;
- account id is the stable identity;
- diagnostic logging already hashes email and does not need raw email if the mapper omits it;
- future notification/audit consumers should justify the exact fields they need.

This issue should not change existing outbox storage payload unless a minimal adaptation is proven necessary.

### Integration Events

Do not introduce external integration events in this issue.

`AccountRegisteredMessage` should be documented as an internal async message derived from the persisted outbox record, not a versioned external contract. If external consumers are introduced later, create a separate versioned integration event design and ADR if needed.

### Published Does Not Mean Consumed

`markPublished()` means the message was successfully handed to Messenger/RabbitMQ. It does not mean the async side effect succeeded. Consumer failure belongs to Messenger retry/failure transport and handler idempotency, not to resetting the outbox record.

### Duplicate Delivery

A crash after Messenger dispatch and before `markPublished()` can publish the same event again on the next outbox run. Concrete handlers must remain idempotent.

## 8. Test Strategy

Add or update tests for:

### Mapper Registry

- delegates to the first matching mapper;
- calls `supports()` on mappers in order;
- fails clearly when no mapper supports a record;
- does not depend on IdentityAccess concrete classes.

### AccountRegistered Mapper

- supports only `identity_access.account_registered`;
- maps `eventId`, `outboxId`, `accountId`, `registeredAt`, and `isSystemAdmin` as selected;
- ignores email by default unless explicitly retained;
- rejects missing `account_id`;
- rejects empty or non-string `account_id`;
- rejects invalid or missing `registered_at`;
- rejects non-boolean `is_system_admin` if the message includes it;
- optionally rejects mismatch between `aggregateId` and `payload.account_id`;
- proves no password, password hash, refresh token, refresh token hash, access token, or session state enters the concrete message.

### OutboxPublisher

- dispatches concrete `AccountRegisteredMessage`, not `OutboxMessage`;
- marks published after concrete-message dispatch succeeds;
- records failure if concrete-message dispatch fails;
- records failure if mapping fails;
- does not mark published on mapping or dispatch failure;
- continues to the next record after mapping or dispatch failure;
- keeps zero/invalid limit behavior unchanged.

### PublishOutboxCommand

- still prints selected/published/failed/skipped counts;
- uses the refactored publisher path in tests with concrete mapped messages;
- command failure behavior remains limited to unrecoverable publisher-level exceptions.

### AccountRegistered Handler

- accepts `AccountRegisteredMessage`;
- contains no handler-level `event_type` filtering;
- calls `tryStart($message->eventId, self::class)`;
- skips duplicate delivery when `tryStart()` returns `false`;
- performs side-effect/logging only after `tryStart()` succeeds;
- calls `markProcessed($message->eventId, self::class)` after the side effect succeeds;
- keeps idempotency work inside `TransactionalInterface`.

### Processed Message Store

No tests currently appear to cover `DoctrineProcessedMessageStore`. Add focused tests if practical:

- first `tryStart(eventId, handlerName)` returns true;
- second `tryStart(eventId, sameHandlerName)` returns false;
- `tryStart(eventId, differentHandlerName)` returns true;
- `markProcessed(eventId, handlerName)` updates only that pair.

### Messenger Wiring and Routing

- `OutboxMessageMapperInterface` implementations are tagged through `_instanceof`;
- `OutboxMessageMapperRegistry` receives tagged mappers;
- `AccountRegisteredMessage` is routed explicitly to `async`;
- generic `OutboxMessage` routing is removed if unused;
- no broad `App\Boardly\*` routing exists;
- command/query buses remain synchronous.

### Verification Commands

The implementation should run:

```text
php -l <new-or-changed-php-file>
php bin/console lint:container
php bin/console debug:config framework messenger
php bin/console debug:messenger
php bin/console boardly:outbox:publish --limit=10 --env=test
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess
php ./vendor/bin/phpunit
php /opt/homebrew/bin/composer phpstan
```

And architecture searches:

```text
rg "OutboxMessage" src/Boardly src/Shared config tests
rg "event_type|eventType" src/Boardly/IdentityAccess/Infrastructure
rg "MessageBusInterface" src/Boardly src/Shared
rg "boardly.outbox_message_mapper|OutboxMessageMapperInterface|OutboxMessageMapperRegistry" src config tests
```
