---
name: symfony-cqrs-bus-boundary
description: "Enforce Boardly HTTP controller CQRS bus boundaries: controllers dispatch commands through CommandBusInterface and queries through QueryBusInterface, never concrete application handlers."
---

# Symfony CQRS Bus Boundary Skill

Use this skill whenever creating, reviewing, or modifying Symfony HTTP controllers, application commands, application queries, command handlers, query handlers, or bus configuration.

## Hard rule

HTTP controllers must never inject or invoke concrete Application handlers directly.

Controllers must:

- dispatch commands through `CommandBusInterface`;
- dispatch queries through `QueryBusInterface`;
- map HTTP request DTOs/route params/cookies/headers into Command or Query objects;
- map application results into HTTP responses.

Concrete handlers must be registered on the correct bus:

- command handlers on `command.bus`;
- query handlers on `query.bus`.

Direct calls like this are architecture violations:

```php
($this->createProjectHandler)($command);
$this->createProjectHandler->__invoke($command);
$this->createProjectHandler->handle($command);
```

## Allowed controller dependency direction

Allowed in HTTP controllers:

```php
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
```

Forbidden in HTTP controllers:

```php
use App\Boardly\SomeContext\Application\SomeUseCase\SomeUseCaseHandler;
use Symfony\Component\Messenger\MessageBusInterface;
```

## Expected HTTP flow

```text
HTTP Request
-> Request DTO / route params / cookies / headers
-> Command or Query
-> CommandBusInterface / QueryBusInterface
-> Application handler
-> Result
-> HTTP response mapping
```

## Workflow

1. Identify whether the controller action mutates state or reads state.
2. For mutations, create/map to an Application Command.
3. For reads, create/map to an Application Query.
4. Inject only `CommandBusInterface` and/or `QueryBusInterface` into the controller.
5. Dispatch through the correct bus.
6. Ensure the concrete handler is registered on `command.bus` or `query.bus`.
7. Verify no controller imports concrete handler classes.
8. Verify no controller calls Symfony `MessageBusInterface` directly.
9. Add or update tests/review notes if an existing controller violated the rule.

## Output format

## 1. Decision

State whether the controller uses the correct bus boundary.

## 2. Controller dependencies

List allowed and forbidden dependencies.

## 3. Command/query mapping

Show which HTTP input maps to which command/query.

## 4. Handler registration

State whether the handler belongs to `command.bus` or `query.bus`.

## 5. Violations found

List concrete handler injections or direct calls, if any.

## 6. Fix

Describe the minimal fix.

## Verification

Recommended checks:

```bash
git grep -n "Handler" -- 'src/Boardly/**/Interfaces/Http/Controller/*.php'
git grep -n "MessageBusInterface" -- 'src/Boardly/**/Interfaces/Http/Controller/*.php'
git grep -n "->__invoke\|->handle\|(\$this->.*Handler)" -- 'src/Boardly/**/Interfaces/Http/Controller/*.php'
php bin/console debug:messenger
php bin/console lint:container
```

Adapt paths/commands to the actual repository structure.

## Boardly rules

- Controllers are interface adapters only.
- Controllers must not know concrete Application handlers.
- Controllers must not dispatch Symfony `MessageBusInterface` directly.
- Commands represent user intentions and go through `CommandBusInterface`.
- Queries represent reads and go through `QueryBusInterface`.
- Application handlers own use-case orchestration, not HTTP controllers.
