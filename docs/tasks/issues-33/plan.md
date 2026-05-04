# Issue 33 Plan: Refactor Outbox Publishing to Concrete Event Messages

## Scope

This is an implementation plan only. Do not implement production code during this planning step.

The plan follows:

- `docs/architecture/project-architecture-rules.md`
- `docs/architecture/application-bus-rules.md`
- `docs/architecture/async-message-handler-rules.md`
- `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`

Core target:

```text
OutboxRecord
-> OutboxMessageMapperRegistry
-> concrete AccountRegisteredMessage
-> Messenger async routing
-> AccountRegistered handler accepting AccountRegisteredMessage
```

Existing outbox table storage remains unchanged. Application commands and queries remain synchronous.

## Step 1: Add Shared Mapper Extension Point

Commit goal:

```text
Add publishing-side outbox mapper extension point.
```

Files to add:

```text
src/Shared/Infrastructure/Outbox/OutboxMessageMapperInterface.php
src/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistry.php
tests/Boardly/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistryTest.php
```

Implementation notes:

- `OutboxMessageMapperInterface` should support mapping an `OutboxRecord` to an `object`.
- The interface should not mention IdentityAccess or any Boardly concrete message class.
- `OutboxMessageMapperRegistry` should accept `iterable<OutboxMessageMapperInterface>`.
- The registry should delegate to the first mapper whose `supports($record)` returns true.
- If no mapper supports the record, throw a clear exception with the unsupported `eventType`.
- Keep this registry parallel to `OutboxEventSerializerRegistry`, but do not combine the two responsibilities.

Tests:

- delegates to a matching mapper;
- checks non-matching mappers first;
- throws a clear exception when no mapper supports the record;
- proves the registry can be constructed without IdentityAccess dependencies.

Verification:

```text
php -l src/Shared/Infrastructure/Outbox/OutboxMessageMapperInterface.php
php -l src/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistry.php
php -l tests/Boardly/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistryTest.php
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistryTest.php
```

## Step 2: Add IdentityAccess Concrete Message and Mapper

Commit goal:

```text
Add AccountRegistered concrete outbox message mapping.
```

Files to add:

```text
src/Boardly/IdentityAccess/Infrastructure/Message/AccountRegisteredMessage.php
src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapper.php
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapperTest.php
```

Implementation notes:

- `AccountRegisteredMessage` is an internal async message, not a domain event and not an external integration event.
- The message must include `eventId` for idempotency.
- Recommended message fields:
  - `outboxId`
  - `eventId`
  - `accountId`
  - `registeredAt`
  - `isSystemAdmin`
- Default recommendation: avoid raw `email` unless a real consumer requires it. Existing outbox payload can still contain email; the mapper can ignore it in this issue.
- `AccountRegisteredOutboxMessageMapper` should support only `identity_access.account_registered`.
- The mapper should validate required payload fields and throw clear exceptions for invalid persisted payloads.
- The mapper should not depend on the `AccountRegistered` domain event class because publishing works from persisted records.

Tests:

- supports `identity_access.account_registered`;
- does not support another event type;
- maps expected fields from `OutboxRecord`;
- preserves `eventId` as the idempotency key;
- maps `registered_at` to `DateTimeImmutable`;
- rejects missing or invalid `account_id`;
- rejects missing or invalid `registered_at`;
- rejects non-boolean `is_system_admin`;
- optionally rejects mismatch between `aggregateId` and `payload.account_id`;
- proves password, password hash, refresh token, refresh token hash, access token, and session state are not included in `AccountRegisteredMessage`;
- if email is included despite the default recommendation, prove raw email is not logged by the handler.

Verification:

```text
php -l src/Boardly/IdentityAccess/Infrastructure/Message/AccountRegisteredMessage.php
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapper.php
php -l tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapperTest.php
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapperTest.php
```

## Step 3: Refactor OutboxPublisher

Commit goal:

```text
Dispatch mapped concrete outbox messages.
```

Files to modify:

```text
src/Shared/Infrastructure/Outbox/OutboxPublisher.php
tests/Boardly/Shared/Infrastructure/Outbox/OutboxPublisherTest.php
tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php
```

Implementation notes:

- Inject `OutboxMessageMapperRegistry` into `OutboxPublisher`.
- Replace `OutboxMessage::fromRecord($record)` with `$mapperRegistry->map($record)`.
- Dispatch the mapped concrete message through the existing `event.bus` injection.
- Preserve `markPublished($record->id, $now)` only after dispatch succeeds.
- Preserve `recordFailure($record->id, $error, $now->modify('+60 seconds'))`.
- Catch mapping failures and dispatch failures per record.
- Do not let one failed record stop later records.
- Do not add IdentityAccess dependencies to `OutboxPublisher`.

Decision:

- Mapping failures should call `recordFailure(...)`, not bubble out of the whole publish command and not mark the record published.
- The generic `OutboxMessage` class may remain temporarily if still referenced, but publishing should no longer dispatch it.

Tests:

- dispatches `AccountRegisteredMessage`, not `OutboxMessage`;
- marks published after mapped-message dispatch succeeds;
- records failure when dispatch throws;
- records failure when mapper throws;
- does not mark published when mapping fails;
- continues with the next record after mapping failure;
- continues with the next record after dispatch failure;
- preserves selected/published/failed/skipped counts;
- `PublishOutboxCommandTest` uses the refactored concrete mapping path.

Verification:

```text
php -l src/Shared/Infrastructure/Outbox/OutboxPublisher.php
php -l tests/Boardly/Shared/Infrastructure/Outbox/OutboxPublisherTest.php
php -l tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox/OutboxPublisherTest.php
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox/Command/PublishOutboxCommandTest.php
```

## Step 4: Refactor AccountRegisteredOutboxMessageHandler

Commit goal:

```text
Consume AccountRegisteredMessage directly.
```

Files to modify or add:

```text
src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php
tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php
```

Implementation notes:

- Change handler signature to:

```php
public function __invoke(AccountRegisteredMessage $message): void
```

- Remove the `event_type` filter.
- Keep `ProcessedMessageStoreInterface` idempotency.
- Keep `TransactionalInterface` transaction boundary.
- Keep `tryStart($message->eventId, self::class)`.
- Keep `markProcessed($message->eventId, self::class)` after the side effect/logging succeeds.
- Keep duplicate delivery skipped when `tryStart()` returns false.
- Do not move domain logic into this async handler.
- Do not activate, approve, reject, or otherwise mutate Account lifecycle here.

Tests:

- handler accepts `AccountRegisteredMessage`;
- no generic `OutboxMessage` handler-level event type filtering remains;
- calls `tryStart($eventId, self::class)`;
- skips duplicate delivery when `tryStart()` returns false;
- logs/executes side effect only when `tryStart()` succeeds;
- calls `markProcessed($eventId, self::class)` after side effect succeeds;
- does not call `markProcessed()` if the side effect throws;
- transaction wraps idempotency and side-effect behavior;
- raw email is not logged if email remains available anywhere.

Verification:

```text
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php
php -l tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandlerTest.php
```

## Step 5: Update Symfony Wiring

Commit goal:

```text
Wire outbox message mappers and explicit concrete async routing.
```

Files to modify:

```text
config/services.yaml
config/packages/messenger.yaml
```

Possible test to add:

```text
tests/Boardly/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistryContainerTest.php
tests/Boardly/Shared/Infrastructure/Messenger/MessengerRoutingTest.php
```

Implementation notes:

- Add `_instanceof` tagging for `OutboxMessageMapperInterface`:

```yaml
App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface:
    tags:
        - { name: boardly.outbox_message_mapper }
```

- Wire `OutboxMessageMapperRegistry`:

```yaml
App\Shared\Infrastructure\Outbox\OutboxMessageMapperRegistry:
    arguments:
        $mappers: !tagged_iterator boardly.outbox_message_mapper
```

- Keep existing outbox event serializer tagging unchanged.
- Keep `OutboxPublisher` dispatching through `event.bus`.
- Route `AccountRegisteredMessage` to async explicitly:

```yaml
App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage: async
```

- Remove generic `OutboxMessage` async routing if no publishing path dispatches it anymore.
- Do not add broad routing such as `App\Boardly\*: async`.
- Keep `command.bus` and `query.bus` synchronous.

Tests/checks:

- container can autowire mapper registry;
- AccountRegistered mapper is tagged;
- `AccountRegisteredMessage` has async sender/routing;
- generic `OutboxMessage` routing is absent if unused;
- command/query buses are not routed to async.

Verification:

```text
php bin/console lint:container
php bin/console debug:config framework messenger
php bin/console debug:messenger
```

## Step 6: Verification

Commit goal:

```text
Run focused and full verification for issue 33.
```

PHP lint for new/changed PHP files:

```text
php -l src/Shared/Infrastructure/Outbox/OutboxMessageMapperInterface.php
php -l src/Shared/Infrastructure/Outbox/OutboxMessageMapperRegistry.php
php -l src/Boardly/IdentityAccess/Infrastructure/Message/AccountRegisteredMessage.php
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageMapper.php
php -l src/Shared/Infrastructure/Outbox/OutboxPublisher.php
php -l src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountRegisteredOutboxMessageHandler.php
```

Symfony/container/Messenger verification:

```text
php bin/console lint:container
php bin/console debug:config framework messenger
php bin/console debug:messenger
php bin/console boardly:outbox:publish --limit=10 --env=test
```

Focused PHPUnit:

```text
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess
```

Full PHPUnit and static analysis:

```text
php ./vendor/bin/phpunit
php /opt/homebrew/bin/composer phpstan
```

Architecture searches:

```text
rg "OutboxMessage" src/Boardly src/Shared config tests
rg "event_type|eventType" src/Boardly/IdentityAccess/Infrastructure
rg "MessageBusInterface" src/Boardly src/Shared
rg "boardly.outbox_message_mapper|OutboxMessageMapperInterface|OutboxMessageMapperRegistry" src config tests
```

Expected search outcomes:

- `OutboxPublisher` no longer dispatches `OutboxMessage`.
- `AccountRegisteredOutboxMessageHandler` no longer accepts `OutboxMessage`.
- `AccountRegisteredOutboxMessageHandler` has no `eventType` filtering.
- `OutboxMessage` has no async routing if unused.
- `MessageBusInterface` remains limited to infrastructure adapters such as bus adapters and outbox publisher.
- mapper interface, registry, and tags appear only in Shared infrastructure, config, and tests.

## Acceptance Criteria

- `OutboxPublisher` dispatches concrete mapped messages, not generic `OutboxMessage`.
- `OutboxPublisher` does not depend on IdentityAccess classes.
- `AccountRegisteredOutboxMessageHandler` accepts `AccountRegisteredMessage`, not `OutboxMessage`.
- No AccountRegistered handler-level `event_type` filtering remains.
- Mappers are registered through `_instanceof` plus tagged iterator.
- `AccountRegisteredMessage` is routed to `async` explicitly.
- Idempotency guard remains `event_id + handler class`.
- Existing outbox storage remains unchanged.
- Existing command/query buses remain synchronous.
- Tests cover registry, mapper, publisher, handler idempotency, and routing assumptions.

## Non-Goals

- Do not implement real email, search, audit, notification, or reporting side effects.
- Do not introduce external integration-event contracts.
- Do not make application commands async.
- Do not change the outbox table schema.
- Do not change `DoctrineOutbox` storage behavior unless implementation reveals a required minimal adaptation.
- Do not move domain logic into async handlers.
- Do not change RegisterAccount domain/application behavior.
- Do not read env files, secrets, dumps, or unrelated generated files.

## Suggested Small Commit Sequence

1. `Add outbox message mapper registry`
2. `Add AccountRegistered outbox message mapper`
3. `Dispatch concrete outbox messages from publisher`
4. `Consume AccountRegisteredMessage directly`
5. `Wire concrete outbox message routing`
6. `Add verification coverage for issue 33`
