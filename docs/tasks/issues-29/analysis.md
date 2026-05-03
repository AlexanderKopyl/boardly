# Issue 29 Analysis: Transactional Outbox for Domain Event Delivery

## Reading Scope

This analysis is based on the requested repository reading scope only.

The requested ADR path `docs/adr/0003-use-transactional-outbox-for-domain-events.md` does not exist in the repository. The matching accepted ADR-0003 file referenced by `AGENTS.md` is `docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md`, and that is the ADR used for this analysis.

## Architectural Recommendation

Implement a minimal transactional outbox that stores durable domain event records in PostgreSQL inside the same Doctrine transaction as the business mutation, then publishes committed records later through Symfony Messenger/RabbitMQ.

The recommended registration flow is:

```text
CommandBus
-> RegisterAccountHandler
-> Account::register(...)
-> AccountRegistrationResult(account, AccountRegistered)
-> AccountRepositoryInterface::save(account)
-> OutboxInterface::store([AccountRegistered])
-> DB commit
-> OutboxPublisher reads unpublished outbox records
-> OutboxPublisher dispatches messages through Messenger/RabbitMQ
-> Consumers handle side effects asynchronously
```

This matches ADR-0003 and `docs/architecture/application-bus-rules.md`: command handlers coordinate use cases and may store domain events through an outbox port, while Messenger/RabbitMQ publishing happens after commit through infrastructure.

## 1. Current Event Flow

`Account::register(...)` returns an `AccountRegistrationResult`.

`AccountRegistrationResult` currently contains:

- `account(): Account`
- `event(): AccountRegistered`

`AccountRegistered` currently contains:

- `accountId(): AccountId`
- `email(): Email`
- `isSystemAdmin(): bool`
- `registeredAt(): DateTimeImmutable`

`RegisterAccountHandler` calls `Account::register(...)`, assigns `$domainResult`, then extracts only `$domainResult->account()`. It saves the account through `AccountRepositoryInterface::save($account)` and returns `RegisterAccountResult`.

The event is lost at this point because `RegisterAccountHandler` never calls `$domainResult->event()` and no outbox store or publisher exists in the current registration flow.

The current transaction boundary is explicit:

- `RegisterAccountHandler::__invoke()` delegates the whole registration operation to `TransactionalInterface::transactional(...)`.
- `DoctrineTransactional` implements that port with Doctrine `EntityManagerInterface::wrapInTransaction(...)`.
- Account persistence currently happens inside that boundary.

The outbox write must be added inside the same callback after account save and before the transaction returns.

## 2. Domain Event Contract

No shared domain event contract was found at `src/Boardly/SharedKernel/Domain/Event/`; that directory does not currently exist.

ADR-0003 points the shared domain event contract to:

```text
src/Boardly/SharedKernel/Domain/Event/DomainEvent.php
```

The implementation should add only a minimal shared contract if it is needed by `OutboxInterface::store(array $events): void` and serialization. `AccountRegistered` should implement that contract so the outbox port can accept domain events consistently.

Keep event metadata minimal for this milestone. `AccountRegistered` already carries the business payload and occurrence time through `registeredAt`. Do not add a broad metadata envelope, causation id, correlation id, actor id, tenant id, tracing fields, or versioning framework until a real use case requires it.

Recommended minimum:

- marker or small `DomainEvent` contract in SharedKernel;
- `AccountRegistered` implements it;
- event id can be generated for the outbox record if the domain event does not own one yet;
- outbox record metadata remains infrastructure metadata, not domain state.

## 3. Outbox Application Port

Add the application-level port at:

```text
src/Shared/Application/Outbox/OutboxInterface.php
```

Expected method shape:

```text
store(array $events): void
```

The application layer should depend on this port because `RegisterAccountHandler` needs a durable way to store events without knowing Doctrine, DBAL, Messenger, RabbitMQ, or Symfony transport details.

The port belongs under `src/Shared/Application` because outbox is technical application support. It is not Boardly business language and should not be placed inside `IdentityAccess` or the domain model.

## 4. Outbox Persistence Model

Proposed infrastructure location:

```text
src/Shared/Infrastructure/Outbox/
```

Required responsibilities:

- persist outbox records;
- serialize domain event payloads explicitly;
- load unpublished records that are available for publishing;
- mark records as published;
- increment attempts and store `last_error` on failure.

Keep the first implementation minimal. A single Doctrine/DBAL adapter can implement the application port and the publisher-facing read/update operations if the code remains clear. Split into repository/service classes only when serialization, publication, retry, and persistence responsibilities start to blur.

Prefer DBAL for the outbox table unless there is a strong reason to model outbox rows as ORM entities. The current account persistence uses ORM entities, but outbox records are technical append/update rows and DBAL keeps the adapter explicit without pretending the outbox is a domain aggregate.

## 5. Outbox Table Design

Proposed PostgreSQL-compatible migration:

```text
CREATE TABLE outbox_messages (
    id UUID NOT NULL,
    event_id UUID NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) DEFAULT NULL,
    aggregate_id VARCHAR(255) DEFAULT NULL,
    payload JSONB NOT NULL,
    occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    attempts INT DEFAULT 0 NOT NULL,
    last_error TEXT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY (id)
);
```

Use `JSONB` on PostgreSQL. If the Doctrine migration must remain platform-neutral, `JSON` is acceptable through DBAL, but the generated SQL should be checked for PostgreSQL compatibility.

Recommended indexes:

- `idx_outbox_unpublished_available` on `(published_at, available_at, created_at)` for unpublished lookup and stable ordering;
- partial PostgreSQL index on `(available_at, created_at)` where `published_at IS NULL` if the migration intentionally uses PostgreSQL-specific SQL;
- `idx_outbox_event_id` or a unique constraint on `event_id` if event ids must be deduplicated at storage/publish boundaries.

Publisher lookup should filter:

```text
published_at IS NULL
AND available_at <= now
ORDER BY available_at ASC, created_at ASC
LIMIT :batchSize
```

## 6. Serialization Strategy

Do not use PHP `serialize()` for durable events.

Do not JSON-encode domain objects or value objects directly.

`AccountRegistered` should be serialized into an explicit stable payload, for example:

```json
{
  "account_id": "018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d",
  "email": "registered.account@example.com",
  "is_system_admin": false,
  "registered_at": "2026-05-03T10:15:30+00:00"
}
```

Recommended `event_type` for this milestone:

```text
identity_access.account_registered
```

This is more stable than a PHP class name while still simple. If the team wants faster implementation and accepts tighter PHP coupling, the class name can be stored initially, but the plan should treat that as an explicit trade-off.

`aggregate_type` should be `account` or `identity_access.account`, and `aggregate_id` should be the account id string.

## 7. RegisterAccountHandler Integration

`RegisterAccountHandler` should inject `OutboxInterface`.

Inside the existing `TransactionalInterface::transactional(...)` callback:

1. validate and normalize command input;
2. check duplicate email;
3. create hash, id, and timestamp;
4. call `Account::register(...)`;
5. save the returned account;
6. store the returned `AccountRegistered` event through `OutboxInterface::store([$domainResult->event()])`;
7. return `RegisterAccountResult`.

Duplicate email must not store an outbox event because the handler throws before account creation and before outbox storage.

Outbox storage must not happen outside the existing transaction callback. If outbox storage fails, the account save must roll back with the transaction.

## 8. Publisher Design

Proposed publisher:

```text
src/Shared/Infrastructure/Outbox/OutboxPublisher.php
```

Proposed CLI command:

```text
php bin/console boardly:outbox:publish
```

Publisher responsibility:

- read unpublished outbox records whose `available_at <= now`;
- dispatch each record through infrastructure Messenger, preferably `event.bus` or an explicit async message route;
- mark the record published on successful dispatch;
- increment `attempts` and store `last_error` on failed dispatch;
- be safe to run repeatedly.

The publisher must publish only committed records because it runs outside the registration transaction and reads from the database after commit.

Repeated runs should skip records with `published_at IS NOT NULL`. Duplicate publication can still happen around crash boundaries, so consumers must be idempotent and event id should be available to consumers.

## 9. Messenger Boundary

Allowed:

```text
OutboxPublisher -> Symfony MessageBusInterface / event.bus / async transport
```

Forbidden:

```text
Domain model -> Messenger
RegisterAccountHandler -> MessageBusInterface
Controller -> dispatch domain event
CommandBus -> dispatch AccountRegistered
Doctrine listener -> publish business event
```

`RegisterAccountHandler` may store events through `OutboxInterface`; it must not publish them to Messenger/RabbitMQ directly.

Messenger routing should stay explicit. Do not add broad async routing such as `App\Boardly\*: async`.

## 10. Transaction and Reliability Risks

The key reliability decision is:

```text
account save + outbox store must commit in the same DB transaction
```

Risks:

- if outbox storage happens after the transaction, the event can still be lost;
- if Messenger dispatch happens inside registration, RabbitMQ outage can break account registration;
- if publisher marks published before dispatch, a crash can lose the event;
- if publisher dispatches then crashes before marking published, a later run can dispatch again;
- if multiple publishers read the same row concurrently, duplicate dispatch is possible;
- if serialization changes without compatibility discipline, old outbox records may become unpublishable.

Recommended first-step concurrency stance:

- filter out already published records;
- mark success/failure per record;
- keep consumers idempotent using `event_id`;
- run a single publisher process as the operational default for the first implementation;
- add row locking such as `FOR UPDATE SKIP LOCKED` only if multiple publisher workers are intentionally supported or the first implementation can add it cleanly.

RabbitMQ outage must not break registration. It should only cause outbox rows to remain unpublished or have failed attempts recorded by the publisher.

Retry/backoff should be explicit even if minimal. The first implementation can use `attempts`, `last_error`, and `available_at` to delay retries after failure. A hard max-attempts or dead-letter state can remain a follow-up if issue #29 only needs basic retry visibility, but the lack of a poison-record policy should be documented operationally.

Publisher observability should include:

- selected record count;
- published count;
- failed count;
- skipped count;
- batch size;
- oldest unpublished record age;
- failure logs with `outbox_id`, `event_id`, `event_type`, and attempt number.

Do not log full payloads by default because event payloads may later contain sensitive fields.

## 11. Tests Needed

Application tests:

- `RegisterAccountHandler` stores `AccountRegistered` in outbox after saving account;
- duplicate email throws and does not store an outbox event;
- account save and outbox store execute inside the same `TransactionalInterface` boundary;
- outbox storage failure rolls back the application transaction in an integration-level test if practical.

Domain/event tests:

- `AccountRegistered` implements the shared domain event contract if one is introduced;
- `AccountRegistrationResult::event()` exposes the event created by registration.

Infrastructure tests:

- outbox persistence stores explicit payload, event type, aggregate fields, timestamps, attempts, and null `published_at`;
- unpublished available records are loaded in deterministic order;
- published records are not loaded;
- `markPublished` sets `published_at`;
- failure recording increments attempts and writes `last_error`.

Publisher tests:

- publisher dispatches unpublished records;
- publisher marks successfully dispatched records as published;
- publisher records failure attempts and last error when dispatch fails;
- publisher does not dispatch already published records;
- repeated publisher runs do not republish records already marked published.

Quality gates:

- focused PHPUnit suites for registration and outbox infrastructure;
- full PHPUnit;
- PHPStan.

## 12. Non-Goals

Do not implement in this issue:

- email notification consumer;
- search indexing consumer;
- audit projection consumer;
- login / refresh token flow;
- account approval flow;
- generic distributed event platform;
- Kafka or external broker abstraction;
- unrelated app features;
- public integration event versioning framework;
- outbox cleanup/archive command unless the issue is expanded.

## 13. Risks and Open Decisions

Real decisions to make during implementation:

- exact event schema shape for `AccountRegistered`;
- event type naming: stable string such as `identity_access.account_registered` vs PHP class name;
- UUID generation source for outbox record ids and event ids;
- publisher batch size default;
- Doctrine DBAL vs ORM for outbox records;
- dispatch raw reconstructed domain events vs integration/message wrapper objects;
- how much retry/backoff policy belongs in the publisher now versus Messenger transport retry later;
- whether to use PostgreSQL-specific partial indexes and `FOR UPDATE SKIP LOCKED` in the first implementation.

Recommended defaults:

- DBAL outbox adapter;
- stable explicit event type string;
- explicit JSON payload;
- publisher batch size configurable with a conservative default;
- dispatch wrapper or explicit message that carries `event_id`, `event_type`, and payload unless all consumers are guaranteed to understand the raw domain event;
- minimal retry in publisher: increment attempts and store last error; leave transport consumer retry to Messenger.

## 14. Operational Acceptance Notes

Operationally acceptable first implementation:

- registration succeeds without RabbitMQ availability;
- unpublished outbox lag is measurable;
- failed publication attempts are visible in the database and logs;
- manual publish command exists;
- repeated publisher runs skip published records;
- duplicate delivery is tolerated by downstream idempotency;
- cleanup/archive for published rows is documented as follow-up if not implemented now.

Suggested rollout order:

1. deploy migration adding `outbox_messages`;
2. deploy code that writes outbox records;
3. enable the publisher command schedule or worker;
4. enable monitoring for unpublished lag and failed attempts.
