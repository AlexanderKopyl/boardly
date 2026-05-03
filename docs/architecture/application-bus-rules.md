# Application Bus Rules

## 1. Purpose

This document defines Boardly rules for application buses.

Boardly uses application commands and queries to enter use cases. Controllers, CLI commands, workers, and other delivery adapters should not depend on concrete use-case handlers directly when a bus abstraction is available.

The bus layer provides a stable application entry point and keeps Symfony Messenger as an infrastructure detail.

---

## 2. Core Rule

Preferred flow for HTTP commands:

```text
HTTP Controller
-> Request DTO
-> Application Command
-> CommandBusInterface
-> Command Handler
-> Application Result
-> HTTP Response
```

Example:

```text
RegisterAccountController
-> RegisterAccountRequestDto
-> RegisterAccountCommand
-> CommandBusInterface::dispatch(...)
-> RegisterAccountHandler
-> RegisterAccountResult
-> JsonResponse
```

Controllers should depend on Boardly bus abstractions, not on concrete handlers and not directly on Symfony Messenger.

---

## 3. Bus Types

Use separate buses for separate intent types.

### Command Bus

Commands represent state-changing user intent.

Examples:

```text
RegisterAccount
CreateProject
CreateIssue
AssignIssue
ChangeIssueStatus
ApproveAccount
DisableAccount
```

Rules:

```text
- commands may mutate state
- commands are handled by command handlers
- command handlers return application results when the caller needs a result
- commands that must complete during an HTTP request are handled synchronously
```

### Query Bus

Queries represent read-only intent.

Examples:

```text
GetCurrentAccount
GetIssueDetails
SearchIssues
GetProjectBoard
GetAuditTrail
```

Rules:

```text
- queries must not mutate state
- query handlers return read models / DTOs
- query handlers may use optimized read-side adapters
```

### Event Bus / Event Publishing

Events describe facts that already happened.

Examples:

```text
AccountRegistered
IssueCreated
IssueStatusChanged
ProjectArchived
```

Rules:

```text
- events are not commands
- domain events are not sent through the command bus
- reliable async side effects must use the transactional outbox
- RabbitMQ/Messenger publishing happens after DB commit through outbox infrastructure when reliability matters
```

---

## 4. Synchronous vs Asynchronous Commands

Application commands that must produce an immediate result for the caller must be synchronous.

Examples:

```text
RegisterAccount -> synchronous
Login -> synchronous
RefreshSession -> synchronous
ChangeIssueStatus -> synchronous core mutation
ApproveAccount -> synchronous
```

Do not route these commands to RabbitMQ just because Symfony Messenger supports async transports.

Bad flow:

```text
Controller -> CommandBus -> RabbitMQ -> Handler later
```

for use cases that must return a result immediately.

Good flow:

```text
Controller -> CommandBus -> Handler immediately -> Result
```

Async messaging is appropriate for side effects:

```text
- notifications
- search indexing
- audit projections
- reporting projections
- external integrations
- email delivery
```

When reliable delivery matters, async side effects go through the transactional outbox.

---

## 5. Abstractions

Recommended application-level contracts:

```text
src/Shared/Application/Bus/CommandBusInterface.php
src/Shared/Application/Bus/QueryBusInterface.php
```

Possible interfaces:

```php
namespace App\Shared\Application\Bus;

interface CommandBusInterface
{
    /**
     * @template T
     * @param object $command
     * @return T
     */
    public function dispatch(object $command): mixed;
}
```

```php
namespace App\Shared\Application\Bus;

interface QueryBusInterface
{
    /**
     * @template T
     * @param object $query
     * @return T
     */
    public function ask(object $query): mixed;
}
```

Marker interfaces such as `Command` and `Query` may be introduced later if they add value.

Do not add ceremony before it is useful.

---

## 6. Symfony Messenger Adapter

Symfony Messenger may implement the bus abstractions.

Recommended placement:

```text
src/Shared/Infrastructure/Symfony/Messenger/MessengerCommandBus.php
src/Shared/Infrastructure/Symfony/Messenger/MessengerQueryBus.php
```

Example command bus adapter:

```php
namespace App\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    public function dispatch(object $command): mixed
    {
        return $this->handle($command);
    }
}
```

Rules:

```text
- controllers depend on CommandBusInterface / QueryBusInterface
- controllers do not depend on Symfony MessageBusInterface directly
- application code does not depend on Symfony Messenger
- Symfony Messenger remains an infrastructure detail
```

---

## 7. Messenger Configuration Direction

Prefer separate Symfony Messenger buses:

```yaml
framework:
    messenger:
        default_bus: command.bus

        buses:
            command.bus: ~
            query.bus: ~
            event.bus: ~
```

Application commands that must be synchronous must not be routed to async transports.

Keep async routing explicit:

```yaml
framework:
    messenger:
        routing:
            App\Shared\Infrastructure\Outbox\Message\OutboxMessage: async
```

Do not configure broad routing like:

```yaml
App\Boardly\*: async
```

because it can accidentally make core application commands asynchronous.

---

## 8. Controller Rules

Preferred controller dependency:

```php
private CommandBusInterface $commandBus
```

not:

```php
private RegisterAccountHandler $handler
```

and not:

```php
private MessageBusInterface $messageBus
```

Target controller shape:

```php
$result = $this->commandBus->dispatch(new RegisterAccountCommand(
    email: $requestDto->email,
    plainPassword: $requestDto->plainPassword,
    name: $requestDto->name,
));
```

The controller still owns DTO-to-command mapping.

The handler still owns use-case orchestration.

The bus only routes the command to the correct handler.

---

## 9. Handler Rules

Command handlers live in the Application layer.

Example placement:

```text
src/Boardly/IdentityAccess/Application/RegisterAccount/RegisterAccountHandler.php
```

Handler responsibilities:

```text
- execute one application use case
- coordinate application ports
- call domain behavior
- persist through repository ports
- use transaction boundary when needed
- return application result objects when needed
```

Handlers must not return HTTP responses.

Bad:

```php
public function __invoke(RegisterAccountCommand $command): JsonResponse
```

Good:

```php
public function __invoke(RegisterAccountCommand $command): RegisterAccountResult
```

---

## 10. Transaction Boundary

A command bus does not automatically mean transaction management.

Transaction boundaries may be implemented by:

```text
- explicit TransactionalInterface usage inside handlers
- command bus middleware
```

Initial recommendation:

```text
Use explicit TransactionalInterface first.
Introduce transaction middleware only when the rules are clear and consistent.
```

Reason:

```text
Not every command necessarily requires the same transaction behavior.
Some commands may coordinate external checks or read operations before entering a transaction.
```

Do not hide unclear transaction behavior inside middleware too early.

---

## 11. Relationship With Outbox

Command bus and outbox solve different problems.

Command bus:

```text
routes user intent to the correct application handler
```

Outbox:

```text
stores domain/integration events durably in the same DB transaction as business state
```

Do not publish domain events directly through the command bus.

Correct flow:

```text
CommandBus
-> CommandHandler
-> Domain behavior creates event
-> Handler saves aggregate
-> Handler stores event through OutboxInterface
-> DB commit
-> Outbox publisher sends message to Messenger/RabbitMQ after commit
```

---

## 12. Common Mistakes

### 1. Making all commands asynchronous

Bad:

```text
RegisterAccount -> RabbitMQ
```

Problem:

```text
The HTTP caller needs an immediate result.
```

### 2. Injecting Symfony MessageBusInterface directly into controllers

Problem:

```text
Symfony Messenger leaks into the transport adapter and sync/async intent becomes unclear.
```

### 3. Using one bus for commands, queries, and events

Problem:

```text
Intent is mixed and middleware/routing rules become unsafe.
```

### 4. Returning HTTP responses from handlers

Problem:

```text
Application layer becomes coupled to HTTP.
```

### 5. Treating events as commands

Problem:

```text
Events are facts that happened. Commands are requests to do something.
```

### 6. Hiding unclear transactions in middleware too early

Problem:

```text
Developers stop seeing where atomic boundaries actually are.
```

---

## 13. Review Checklist

Before adding or changing command/query handling, check:

```text
- Is this a command, query, or event?
- Does the caller need an immediate result?
- Is the command handled synchronously if needed?
- Does the controller depend on CommandBusInterface or QueryBusInterface?
- Is Symfony MessageBusInterface hidden behind an adapter?
- Is the handler in the Application layer?
- Does the handler return an application result, not HTTP response?
- Are async side effects handled through outbox when reliability matters?
- Is Messenger routing explicit and safe?
- Are transaction boundaries visible and intentional?
```

---

## 14. Summary

Use buses to create stable application entry points.

Remember:

```text
Command = intent to change state
Query = intent to read state
Event = fact that already happened
```

Preferred controller flow:

```text
Request DTO -> Command -> CommandBusInterface -> Handler -> Result -> Response
```

Symfony Messenger is allowed as implementation, but Boardly code should depend on Boardly bus abstractions.
