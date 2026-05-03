# Issue 29 Plan: Transactional Outbox for Domain Event Delivery

## Scope

This is an implementation plan only. Do not implement production code during this planning step.

The plan follows ADR-0003, `docs/architecture/project-architecture-rules.md`, and `docs/architecture/application-bus-rules.md`: domain creates business events, application stores returned events through an outbox port in the same DB transaction as business state, and infrastructure publishes committed outbox records later.

## Step 1: Add Minimal Shared Domain Event Contract If Needed

Files likely to be created/modified:

- `src/Boardly/SharedKernel/Domain/Event/DomainEvent.php`
- `src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php`
- focused domain event tests if current conventions call for them

What changes:

- Add a minimal shared domain event contract under SharedKernel, matching ADR-0003.
- Make `AccountRegistered` implement the contract.
- Keep the contract small; a marker interface is acceptable if no behavior is needed yet.

What must not change:

- Do not add Symfony, Doctrine, Messenger, RabbitMQ, or outbox dependencies to domain code.
- Do not add broad event metadata before there is a real use case.
- Do not change `Account::register(...)` business behavior.

Expected verification:

- PHP lint for changed PHP files.
- Focused domain/event PHPUnit tests if added.
- PHPStan should still accept `AccountRegistered` as a domain type.

## Step 2: Add OutboxInterface Application Port

Files likely to be created/modified:

- `src/Shared/Application/Outbox/OutboxInterface.php`

What changes:

- Add an application-level outbox port with expected shape:

```text
store(array $events): void
```

- Document or type the array as a list of domain events once the shared contract exists.

What must not change:

- Do not reference Doctrine, DBAL, Messenger, RabbitMQ, or Symfony in the port.
- Do not add infrastructure implementation in this step unless the commit intentionally combines small adjacent work.

Expected verification:

- PHP lint for the new interface.
- PHPStan for generic/list PHPDoc correctness.

## Step 3: Design and Add Outbox Persistence Model

Files likely to be created/modified:

- `src/Shared/Infrastructure/Outbox/OutboxRecord.php` or equivalent DTO
- `src/Shared/Infrastructure/Outbox/DoctrineOutboxStore.php` or equivalent adapter
- `src/Shared/Infrastructure/Outbox/OutboxEventSerializer.php`
- optional exception classes under `src/Shared/Infrastructure/Outbox/Exception/`

What changes:

- Define the infrastructure representation of an outbox record.
- Keep responsibilities explicit: storing events, loading unpublished records, marking published, recording failure.
- Prefer DBAL for outbox rows unless ORM proves clearly better.

What must not change:

- Do not model outbox records as domain aggregates.
- Do not leak outbox record types into IdentityAccess domain code.
- Do not publish messages yet.

Expected verification:

- PHP lint for new classes.
- Unit tests for record construction and serializer behavior if split into separate classes.

## Step 4: Add Migration for Outbox Table

Files likely to be created/modified:

- `migrations/VersionYYYYMMDDHHMMSS.php`

What changes:

- Add PostgreSQL-compatible `outbox_messages` table with:

```text
id uuid primary key
event_id uuid not null
event_type varchar not null
aggregate_type varchar null
aggregate_id varchar null
payload json/jsonb not null
occurred_at timestamp not null
available_at timestamp not null
published_at timestamp null
attempts int not null default 0
last_error text null
created_at timestamp not null
```

- Add indexes for unpublished lookup and ordering:

```text
published_at + available_at + created_at
```

or a PostgreSQL partial index:

```text
available_at + created_at WHERE published_at IS NULL
```

What must not change:

- Do not alter the existing `accounts` table.
- Do not add unrelated migrations.
- Do not introduce broker-specific schema.

Expected verification:

- PHP lint for migration file.
- Run migration in test/dev database as appropriate.
- Confirm table and indexes exist.

## Step 5: Implement Event Serialization for AccountRegistered

Files likely to be created/modified:

- `src/Shared/Infrastructure/Outbox/OutboxEventSerializer.php`
- `src/Shared/Infrastructure/Outbox/SerializedOutboxEvent.php` or equivalent DTO
- serializer tests under `tests/Boardly/Shared/Infrastructure/Outbox/` or equivalent convention

What changes:

- Map `AccountRegistered` to stable outbox fields:

```text
event_type: identity_access.account_registered
aggregate_type: account or identity_access.account
aggregate_id: AccountRegistered::accountId()->value()
payload.account_id
payload.email
payload.is_system_admin
payload.registered_at
occurred_at: AccountRegistered::registeredAt()
```

- Generate `event_id` if the domain event does not own one yet.

What must not change:

- Do not use PHP `serialize()`.
- Do not JSON-encode domain/value objects directly.
- Do not expose password hashes, refresh tokens, or unrelated account state.

Expected verification:

- Serializer unit test asserts exact payload and event type.
- PHPStan verifies payload array shapes where practical.

## Step 6: Implement Doctrine Outbox Storage Adapter

Files likely to be created/modified:

- `src/Shared/Infrastructure/Outbox/DoctrineOutboxStore.php`
- `src/Shared/Infrastructure/Outbox/OutboxRecord.php`
- `src/Shared/Infrastructure/Outbox/OutboxEventSerializer.php`
- `config/services.yaml` only if autowiring/aliasing requires explicit configuration
- outbox persistence tests

What changes:

- Implement `OutboxInterface::store(...)`.
- Insert outbox rows using the same database connection/EntityManager transaction as the handler.
- Add operations needed by the publisher:
  - load unpublished available records;
  - mark record published;
  - record failure attempt and last error.

What must not change:

- Do not dispatch Messenger messages from this adapter.
- Do not flush/commit independently outside the current transaction boundary.
- Do not bypass `TransactionalInterface` in application handlers.

Expected verification:

- Unit tests for method behavior with DBAL/EntityManager mocks where useful.
- Integration test persists an outbox record and reloads it from the database.
- Confirm unpublished and published filters work.

## Step 7: Update RegisterAccountHandler to Store AccountRegistered Inside Existing Transaction

Files likely to be modified:

- `src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php`
- `tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php`

What changes:

- Inject `OutboxInterface`.
- After `$this->accounts->save($account)`, call:

```text
$this->outbox->store([$domainResult->event()])
```

- Keep both calls inside the existing `TransactionalInterface::transactional(...)` callback.

What must not change:

- Do not inject `MessageBusInterface`.
- Do not dispatch `AccountRegistered`.
- Do not move registration to async command handling.
- Do not store an outbox event for duplicate email.
- Do not change returned `RegisterAccountResult`.

Expected verification:

- Application unit test asserts successful registration saves account and stores one `AccountRegistered` event.
- Duplicate email test asserts no outbox event is stored.
- Transaction test asserts account save and outbox store run inside the transaction operation.

## Step 8: Implement OutboxPublisher

Files likely to be created/modified:

- `src/Shared/Infrastructure/Outbox/OutboxPublisher.php`
- `src/Shared/Infrastructure/Outbox/OutboxMessage.php` or equivalent dispatch wrapper if chosen
- `src/Shared/Infrastructure/Outbox/DoctrineOutboxStore.php`
- publisher tests

What changes:

- Read unpublished available outbox records in batches.
- Dispatch through infrastructure Messenger, preferably a bus configured for events or explicit async messages.
- Mark each record published after successful dispatch.
- Increment attempts and store `last_error` after failed dispatch.
- Move `available_at` forward on failure if basic backoff is included in the first implementation.
- Treat single publisher process as the first operational default unless row locking is implemented.
- Return a small result/count object if useful for CLI output.

What must not change:

- Do not call application command bus with domain events.
- Do not require RabbitMQ availability during registration.
- Do not mark a record published before dispatch succeeds.
- Do not assume global event ordering.

Expected verification:

- Publisher unit tests for success, failure, and already-published skipping.
- Repeated run test shows published records are not dispatched again.
- If backoff is implemented, failure test asserts `available_at` moves forward.

## Step 9: Add CLI Command `boardly:outbox:publish`

Files likely to be created/modified:

- `src/Shared/Infrastructure/Outbox/Command/PublishOutboxCommand.php`
- `config/services.yaml` only if command registration needs explicit wiring

What changes:

- Add Symfony Console command:

```text
php bin/console boardly:outbox:publish
```

- Options can include batch size and possibly max runtime if useful:

```text
--limit=100
```

- Command delegates to `OutboxPublisher` and reports published/failed/skipped counts.
- Command output and logs should include selected count, published count, failed count, skipped count, batch size, and oldest unpublished age.
- Failure logs should include `outbox_id`, `event_id`, `event_type`, and attempt number, but not full payloads by default.

What must not change:

- Do not implement consumers in this step.
- Do not add queue worker orchestration or deployment scripts unless separately requested.
- Do not publish directly from controllers or command handlers.

Expected verification:

- Console command test or smoke test through Symfony console.
- Manual command run in test/dev environment after migration.
- Failed publish path is observable without exposing payload contents.

## Step 10: Configure Services/Messenger Only Where Necessary

Files likely to be modified:

- `config/services.yaml`
- `config/packages/messenger.yaml`

What changes:

- Add explicit service aliases only if autowiring does not resolve `OutboxInterface`.
- Configure any needed Messenger routing explicitly for the chosen outbox message wrapper.
- Use existing `event.bus`/`async` infrastructure where possible.

What must not change:

- Do not add broad async routing such as `App\Boardly\*: async`.
- Do not route synchronous application commands to RabbitMQ.
- Do not add RabbitMQ routing for consumers that are non-goals.

Expected verification:

- `php bin/console debug:container` for outbox services if needed.
- `php bin/console debug:messenger` if routing is changed.
- Existing command/query bus behavior remains synchronous.

## Step 11: Add Focused Tests

Files likely to be created/modified:

- `tests/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandlerTest.php`
- `tests/Boardly/Shared/Infrastructure/Outbox/OutboxEventSerializerTest.php`
- `tests/Boardly/Shared/Infrastructure/Outbox/DoctrineOutboxStoreTest.php`
- `tests/Boardly/Shared/Infrastructure/Outbox/DoctrineOutboxStoreIntegrationTest.php`
- `tests/Boardly/Shared/Infrastructure/Outbox/OutboxPublisherTest.php`
- optional console command test

What changes:

- Add coverage for:
  - registration stores `AccountRegistered` in outbox;
  - duplicate email does not store an event;
  - save and outbox store occur inside transaction boundary;
  - persistence stores explicit JSON payload;
  - unpublished records load in order;
  - published records are not loaded;
  - success marks records published;
  - failure increments attempts and stores `last_error`;
  - publisher repeat safety.

What must not change:

- Do not add broad end-to-end tests for non-goal consumers.
- Do not weaken existing registration tests around password hashing, unsafe result data, or validation failures.

Expected verification:

- Focused PHPUnit suites pass.
- Integration tests document required migration/table setup, matching existing persistence test conventions.

## Step 12: Run Verification

Files likely to be modified:

- none, unless test fixtures or generated migration files were intentionally changed in earlier steps.

What changes:

- Run final checks:

```text
php -l changed PHP files
php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Application/RegisterAccount
php ./vendor/bin/phpunit tests/Boardly/Shared/Infrastructure/Outbox
php ./vendor/bin/phpunit
php /opt/homebrew/bin/composer phpstan
```

What must not change:

- Do not modify composer files.
- Do not commit unrelated formatting churn.
- Do not alter unrelated bounded contexts.

Expected verification:

- PHPUnit passes.
- PHPStan passes.
- `git diff --name-only` contains only intentional implementation files.
- Deployment notes identify migration first, code second, publisher enablement third, and monitoring fourth.

## Acceptance Criteria Mapping

- `OutboxInterface` exists in an application-level location under `src/Shared/Application`.
- Outbox persistence adapter exists under `src/Shared/Infrastructure`.
- Outbox migration exists.
- `RegisterAccountHandler` stores `AccountRegistered` through `OutboxInterface` inside the same transaction as account persistence.
- No domain entity/service depends on Messenger, RabbitMQ, Symfony EventDispatcher, Doctrine, or outbox infrastructure.
- No controller dispatches domain events.
- No command handler dispatches domain events directly to Messenger/RabbitMQ.
- `OutboxPublisher` can publish unpublished records and mark them as published.
- Failed publishing increments attempts and stores error details.
- Publisher is safe to run repeatedly.
- Tests cover registration outbox storage.
- Tests cover publisher success and failure behavior.
- PHPStan passes.
- PHPUnit passes.
