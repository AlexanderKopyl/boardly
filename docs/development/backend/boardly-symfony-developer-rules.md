# Boardly Symfony Developer Rules

Status: active backend rulebook  
Scope: PHP 8.3 / Symfony 8 backend code in Boardly, especially `src/Boardly/`, `src/Shared/`, `src/SharedKernel/`, `config/`, `migrations/`, tests, console commands, API controllers, Messenger handlers, and infrastructure adapters.

## 1. Purpose

This document defines production-oriented PHP/Symfony development rules for Boardly.

Boardly is a Jira-like project and task management system. It is not a CRM.

The goal is not academic purity. The goal is safe, readable, testable Symfony code that respects Boardly's modular monolith, DDD, Hexagonal Architecture, transactional consistency, and API-first direction.

This document complements:

```text
docs/architecture/project-architecture-rules.md
docs/architecture/http-controller-rules.md
docs/architecture/application-bus-rules.md
docs/architecture/async-message-handler-rules.md
docs/adr/
```

## 2. Baseline PHP rules

New PHP files must follow these rules:

```text
- Use declare(strict_types=1);
- Target PHP 8.3 syntax.
- Use explicit parameter types and return types.
- Use typed properties.
- Prefer constructor property promotion for injected dependencies.
- Prefer readonly classes or private readonly dependencies where appropriate.
- Do not use dynamic properties.
- Do not rely on implicit casts.
- Avoid loose comparisons.
- Avoid mixed. If mixed is unavoidable, normalize it immediately at the boundary.
- Prefer final classes unless extension is intentionally designed.
```

PHPDoc is required for:

```text
- array shapes;
- generic collections;
- complex return structures;
- @throws when the caller is expected to handle the exception;
- non-obvious domain or infrastructure decisions.
```

Do not add mechanical PHPDoc to every trivial typed method if the signature is already clear.

Example:

```php
<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

final readonly class AuthenticateAccountCommand
{
    public function __construct(
        private string $email,
        private string $plainPassword,
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function plainPassword(): string
    {
        return $this->plainPassword;
    }
}
```

## 3. Core architecture rules

Default architecture:

```text
Interfaces / UI -> Application -> Domain
Infrastructure -> Application / Domain ports
```

Forbidden direction:

```text
Domain -> Symfony
Domain -> Doctrine
Domain -> Messenger
Domain -> Redis
Domain -> OpenSearch
Domain -> RabbitMQ
Domain -> HTTP
Application -> Symfony Request/Response
Application -> Doctrine EntityManager directly
Application -> concrete external clients directly
```

Boardly layers:

```text
src/Boardly/<Context>/Domain
src/Boardly/<Context>/Application
src/Boardly/<Context>/Infrastructure
src/Boardly/<Context>/Interfaces
src/Shared
src/SharedKernel
```

## 4. Module and bounded context rules

Each bounded context owns its own language and behavior.

Current and expected contexts include:

```text
IdentityAccess
Projects
Issues
Workflow
Boards
Collaboration
Notifications
Search
Reporting
Audit
Integrations
Automation
```

Rules:

```text
- Do not put context-specific behavior into src/Shared.
- Do not make src/Shared know concrete Boardly domain events, aggregates, commands, or modules.
- Shared code must be generic technical/application infrastructure with no product-specific branching.
- Cross-context communication should use explicit contracts/events, not direct aggregate access.
```

Bad:

```text
src/Shared/Infrastructure/Outbox/OutboxEventSerializer.php knows AccountRegistered, IssueCreated, ProjectArchived.
```

Good:

```text
Shared registry + context-owned serializers/mappers.
```

## 5. SRP and no god classes

Each class must have one clear reason to change.

Allowed examples:

```text
AuthenticateAccountHandler orchestrates login use case.
RefreshAuthenticationHandler rotates refresh sessions.
DoctrineAccountRepository persists Account aggregate.
JwtAccessTokenIssuer issues JWT access tokens.
RefreshCookieFactory creates/clears refresh cookies.
AccountRegisteredOutboxMessageMapper maps one outbox event type to one message.
```

Forbidden examples:

```text
IdentityAccessManager that registers accounts, authenticates users, issues JWTs, approves accounts, sends messages, and writes logs.
AuthHelper with static methods for cookies, tokens, repositories, and response mapping.
Central service that handles every IdentityAccess exception and every HTTP response manually.
```

Split when a class starts mixing:

```text
- HTTP transport mapping;
- use-case orchestration;
- domain rules;
- persistence;
- token/security implementation;
- message publishing;
- logging;
- OpenAPI documentation;
- external integration calls.
```

## 6. Controllers are interface adapters

HTTP controllers belong to:

```text
src/Boardly/<Context>/Interfaces/Http/Controller/
```

Controllers may:

```text
- receive request DTOs;
- read route parameters;
- extract cookies/headers when they are transport concerns;
- map request DTOs to application commands/queries;
- call Boardly CommandBusInterface or QueryBusInterface;
- map successful application results to JSON responses;
- create/clear HTTP cookies through a small response/cookie factory.
```

Controllers must not:

```text
- contain business rules;
- verify passwords;
- issue JWTs directly;
- mutate aggregates directly;
- use Doctrine EntityManager;
- dispatch Symfony MessageBusInterface directly;
- publish RabbitMQ messages directly;
- query Redis/OpenSearch directly;
- perform permission decisions that belong to application/domain policy;
- catch and map expected exceptions locally unless explicitly justified.
```

Expected flow:

```text
HTTP Request
-> Request DTO / route params / cookies
-> Command or Query
-> CommandBusInterface / QueryBusInterface
-> Application handler
-> Result
-> Response mapping
```

## 7. Request DTO rules

Request DTOs belong to the Interfaces/HTTP layer.

Placement:

```text
src/Boardly/<Context>/Interfaces/Http/Request/
```

Rules:

```text
- Request DTOs describe public HTTP contract.
- Request DTOs are not application commands.
- Request DTOs are not domain value objects.
- Request DTOs may use Symfony Validator constraints.
- Request DTOs must not contain business behavior.
- Request DTOs must not be passed directly to application handlers.
```

Auth contract rule:

```json
{
  "plainPassword": "..."
}
```

Do not expose public request fields named `password` for current IdentityAccess register/login endpoints unless the backend contract is deliberately changed.

## 8. Application commands, queries, and handlers

Application layer owns use-case orchestration.

Placement:

```text
src/Boardly/<Context>/Application/<UseCase>/
```

Typical files:

```text
<UseCase>Command.php
<UseCase>Query.php
<UseCase>Handler.php
<UseCase>Result.php
```

Handlers may:

```text
- normalize input through value objects;
- load aggregates through repositories/ports;
- check application-level conflicts;
- call domain behavior;
- coordinate transaction boundaries;
- persist through repositories;
- store outbox events when reliable async side effects exist;
- return safe application results.
```

Handlers must not:

```text
- know Symfony Request/Response;
- create HTTP cookies;
- return JsonResponse;
- use Doctrine EntityManager directly;
- call Symfony MessageBusInterface directly;
- know RabbitMQ, Redis, OpenSearch clients;
- call JWT libraries directly when a port exists;
- expose domain aggregates or Doctrine entities as results.
```

## 9. Command/query bus rules

Controllers should call Boardly bus abstractions, not Symfony Messenger directly.

Allowed:

```text
App\Shared\Application\Bus\CommandBusInterface
App\Shared\Application\Bus\QueryBusInterface
```

Forbidden in controllers:

```text
Symfony\Component\Messenger\MessageBusInterface
```

Application commands that return immediate API results must be handled synchronously. Do not route core commands such as login, refresh, approve account, create issue, or change issue status to RabbitMQ by default.

## 10. Domain model rules

Domain owns business behavior and invariants.

Domain may contain:

```text
- aggregates;
- entities;
- value objects;
- domain services;
- domain events;
- domain exceptions;
- domain result objects.
```

Domain must not contain:

```text
- Symfony attributes;
- Symfony services;
- Doctrine repositories or EntityManager;
- HTTP status codes;
- JSON response shapes;
- cookies;
- JWT implementation;
- Messenger messages;
- RabbitMQ/Redis/OpenSearch clients.
```

Good:

```php
$account->approve($now);
$issue->changeStatus($targetStatus, $workflowPolicy, $now);
```

Bad:

```php
$account->setStatus('active');
$issue->status = 'done';
```

## 11. Value object rules

Use value objects when a scalar has domain meaning or validation.

Good candidates:

```text
AccountId
Email
AccountName
PasswordHash
ProjectId
IssueId
IssueKey
WorkflowStatus
Money
```

Rules:

```text
- Value objects are immutable.
- Validate invariants in named constructors or constructors.
- Keep parsing/formatting close to the value.
- Do not create value objects for every trivial scalar.
- Do not let value objects depend on Symfony/Doctrine/HTTP.
```

Named constructors are preferred when normalization is involved:

```php
$email = Email::fromString($input);
```

## 12. Domain event and outbox rules

Domain events describe facts that already happened.

Examples:

```text
AccountRegistered
AccountApproved
IssueCreated
IssueAssigned
IssueStatusChanged
```

Rules:

```text
- Events are not commands.
- Events should use past-tense names.
- Domain event can exist without Messenger publication.
- Outbox publication is required only when reliable async side effects exist.
- Do not create outbox mapper/message/handler chain without a real consumer or side effect.
```

Correct chain when async side effect exists:

```text
Domain Event
-> Outbox record stored in same DB transaction
-> OutboxMessageMapper
-> Infrastructure Message
-> Messenger async transport
-> Message Handler
-> Side effect
```

Bad:

```text
Add AccountApprovedMessageHandler that does nothing just so Messenger does not complain.
```

Good:

```text
AccountApprovedMessageHandler writes a safe structured log, sends a notification, updates audit, or performs another real side effect.
```

## 13. Transaction rules

Use synchronous DB transactions for core state changes.

Required for:

```text
- account registration;
- login refresh-session creation;
- refresh token rotation;
- logout revocation;
- account approval/rejection/disabling;
- project creation/archival;
- issue creation;
- issue assignment;
- issue status transition;
- permission-sensitive mutations;
- storing outbox records with aggregate changes.
```

Rules:

```text
- Core DB transaction must not depend on RabbitMQ availability.
- Redis/OpenSearch/RabbitMQ failures must not invalidate already committed source-of-truth state.
- If an outbox event is needed, store it in the same transaction as the aggregate mutation.
- Async side effects run after commit through outbox/publisher/consumer flow.
```

## 14. Doctrine persistence rules

Doctrine belongs in Infrastructure.

Placement:

```text
src/Boardly/<Context>/Infrastructure/Persistence/Doctrine/
```

Allowed:

```text
- Doctrine entities;
- Doctrine repositories;
- mappers between domain and persistence model;
- DBAL queries for optimized reads;
- migrations.
```

Rules:

```text
- Do not expose Doctrine entities outside Infrastructure.
- Do not return Doctrine entities from controllers.
- Do not put domain behavior into Doctrine entities if the project uses separate domain models.
- Keep database constraints for important invariants.
- Add indexes with a concrete read/write reason.
- Store refresh token hashes only, never raw refresh tokens.
- Use migrations for schema changes; no manual production-only schema edits.
```

## 15. Repository rules

Repositories are ports from Application/Domain perspective and adapters in Infrastructure.

Repository interfaces should express use-case/domain needs, not ORM operations.

Good:

```php
public function get(AccountId $id): Account;
public function findByEmail(Email $email): ?Account;
public function save(Account $account): void;
```

Bad:

```php
public function createQueryBuilder(string $alias): QueryBuilder;
public function findBy(array $criteria): array;
```

Rules:

```text
- Keep repository interfaces small.
- Do not leak Doctrine QueryBuilder through application ports.
- Use query handlers/read repositories for read models and listings.
- Use DB constraints as final consistency protection.
```

## 16. Messenger and async handler rules

Symfony Messenger is an infrastructure mechanism.

Async handlers must assume at-least-once delivery.

Rules:

```text
- Handlers must be idempotent when side effects can be repeated.
- Do not hide core business state mutation inside async handlers.
- Do not require RabbitMQ for a core state change to be valid.
- Retry behavior must be safe.
- Failure handling must be explicit.
- Message payloads should be stable and versioned if exposed outside module boundaries.
```

Default idempotency key direction:

```text
event_id + handler_name
```

## 17. API response rules

API responses are contracts. They must not leak internal models.

Do not expose:

```text
- Doctrine entities;
- domain aggregates directly;
- password hashes;
- plain passwords;
- refresh tokens in JSON;
- refresh token hashes;
- JWT signing details;
- raw exception messages;
- permission matrices unless intentionally designed.
```

Auth rules:

```text
- accessToken may be returned in JSON;
- refresh token must be stored in HttpOnly cookie;
- refresh token must never be returned in JSON;
- frontend uses credentials/include for refresh-cookie requests;
- protected requests use Authorization: Bearer <accessToken>.
```

## 18. Error handling rules

Expected application/domain failures should be mapped centrally.

Preferred direction:

```text
Kernel exception event
-> API exception mapper/subscriber
-> standardized JSON response
```

Rules:

```text
- Controllers should not repeat try/catch mappings.
- Domain exceptions must not leak class names or stack traces.
- Infrastructure exceptions must be converted at the boundary when the caller can act on them.
- Unexpected exceptions remain internal server errors.
```

Standard shape:

```json
{
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Validation shape must match the implemented backend contract and OpenAPI docs.

## 19. Logging rules

Log enough to debug without leaking secrets or personal data.

Allowed fields:

```text
- operation name;
- account_id when needed;
- project_id / issue_id when needed;
- event_id;
- outbox_id;
- message class;
- retry/idempotency key;
- safe status/error code.
```

Do not log:

```text
- plain passwords;
- password hashes;
- raw refresh tokens;
- JWTs;
- cookies;
- API secrets;
- full Authorization headers;
- full request bodies containing credentials;
- unnecessary email addresses in operational logs.
```

For account approval logging, prefer:

```php
$this->logger->info('IdentityAccess account approved.', [
    'event_id' => $message->eventId,
    'outbox_id' => $message->outboxId,
    'account_id' => $message->accountId,
    'approved_at' => $message->approvedAt->format(DATE_ATOM),
]);
```

## 20. Security rules

Auth/security code must be conservative.

Rules:

```text
- Do not store raw refresh tokens.
- Do not return refresh tokens in JSON.
- Do not store access tokens in frontend localStorage by design.
- Do not put dynamic permissions into JWT.
- JWT establishes identity only.
- Backend remains authoritative for authorization.
- Inactive accounts must not receive access tokens or refresh cookies.
- Logout must not reveal refresh token validity state.
- Invalid credentials must not reveal whether email or password was wrong.
```

Production secrets must come from environment/secret storage, not repository files.

## 21. OpenAPI / API documentation rules

OpenAPI is the API contract for FE integration.

Rules:

```text
- Document implemented behavior, not aspirational behavior.
- Keep request field names aligned with real DTOs.
- Register/login must document plainPassword, not password.
- Refresh token must be documented as HttpOnly cookie, not JSON data.
- Bearer JWT auth scheme must be documented for protected endpoints.
- Error schemas must match centralized error format.
- Swagger/OpenAPI attributes must not add business logic to controllers.
```

Do not introduce API Platform unless a separate architectural decision accepts it.

## 22. DTO and result rules

Use DTOs/results for explicit boundaries.

Use DTOs/results for:

```text
- request payloads;
- application command/query input;
- application results;
- read models;
- external API payloads;
- Messenger messages;
- OpenAPI schemas when useful.
```

Do not use DTOs for:

```text
- every private helper call;
- replacing clear value objects mechanically;
- hiding unrelated data in generic Context objects.
```

Rules:

```text
- DTOs must be typed.
- Prefer immutable DTOs.
- DTOs must not contain service dependencies.
- Do not pass Symfony Request, Session, Container, Registry, EntityManager, or ServiceLocator into DTOs.
```

## 23. Method arguments and context objects

Long scalar argument lists are design smell.

Rules:

```text
- Public service/handler methods should normally have one command/query/result object or a small number of clear arguments.
- More than 3-4 scalar arguments usually means a missing DTO/value object.
- More than 2 nullable arguments in one method is a smell.
- Do not pass generic array $context without a documented shape and meaning.
- Context objects must contain data, not dependencies.
```

Good:

```php
public function __invoke(AuthenticateAccountCommand $command): AuthenticateAccountResult
```

Bad:

```php
public function authenticate(string $email, string $password, ?string $ua, ?string $ip, bool $remember, ?string $source): array
```

## 24. Constants and magic values

Use constants for reusable contract values.

Good candidates:

```text
- event types;
- cookie names;
- route names when reused;
- message names;
- array keys used across files;
- error codes;
- status strings when not represented by value objects/enums.
```

Rules:

```text
- Constants should live close to the owning concept.
- Use private const by default.
- Use public const only for shared contracts.
- Do not hide environment-specific configuration as constants.
```

Prefer value objects/enums for domain states.

## 25. Naming rules

Names must describe responsibility.

Good names:

```text
AuthenticateAccountHandler
RefreshAuthenticationHandler
ApproveAccountHandler
DoctrineAccountRepository
RefreshTokenHasher
AccessTokenIssuer
RefreshCookieFactory
AccountApprovedMessageHandler
```

Weak names:

```text
AuthManager
CommonHelper
IdentityService
DataProcessor
Utils
MainHandler
```

Rules:

```text
- Class name should answer what role the object owns.
- Method name should be precise: authenticate, refresh, revoke, approve, reject, map, issue, hash.
- Avoid handle() except Messenger handlers where __invoke() or handler naming makes the message clear.
```

## 26. Static methods

Static methods are allowed for named constructors and pure deterministic helpers.

Allowed:

```php
Email::fromString($email);
AccountId::fromString($id);
```

Forbidden:

```text
- static service locators;
- static methods accessing DB/config/container/request/session;
- static mutable state;
- static integration clients.
```

## 27. Dependency rules

Rules:

```text
- Inject dependencies through constructors.
- Do not instantiate external clients in business/application services.
- Application depends on ports for external/framework concerns.
- Infrastructure implements ports.
- Local concrete dependencies are allowed when they do not cross architectural boundaries and do not create fake extension points.
- Do not inject service containers into normal services.
```

Symfony-specific dependencies should stay in Infrastructure or Interfaces.

## 28. Testing rules

Tests must match the layer being tested.

Domain tests:

```text
- no Symfony kernel;
- no Doctrine;
- no real clock/randomness unless injected;
- assert invariants and lifecycle behavior.
```

Application tests:

```text
- use fakes for repositories/ports;
- assert orchestration, transaction usage, outbox storage where applicable;
- assert safe result shape;
- no HTTP kernel unless testing integration.
```

Infrastructure tests:

```text
- test Doctrine repositories/mappers;
- test JWT issuer/verifier;
- test token hasher/generator;
- test outbox mappers/publishers;
- test Messenger handlers with fake logger/adapters when possible.
```

HTTP/security tests:

```text
- test request/response contract;
- test cookies and headers;
- test centralized errors;
- test protected routes and JWT authentication behavior.
```

Tests must cover security-sensitive negative paths, not only happy paths.

## 29. Verification commands

Use project composer scripts when available.

Common checks:

```bash
composer validate
composer install
php bin/console lint:container
php bin/console lint:yaml config
php bin/console debug:router
php bin/console debug:container
php bin/console debug:autowiring
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --dry-run
php bin/console doctrine:schema:validate
php ./vendor/bin/phpunit
composer phpstan
composer qa
```

For Messenger/outbox changes:

```bash
php bin/console debug:messenger
php bin/console messenger:consume async -vv
```

For OpenAPI changes:

```bash
php bin/console debug:router | grep api/doc
curl -s http://localhost/api/doc.json | jq '.paths | keys'
```

For changed PHP files:

```bash
php -l path/to/changed-file.php
```

## 30. PR / task summary template

Every backend change summary should include:

```text
Changed:
- <file/path.php>: <what changed and why>

Architecture:
- Layer boundaries respected: yes/no + note
- Controller/console command kept thin: yes/no/not applicable
- Application handler owns orchestration: yes/no/not applicable
- Domain invariants protected: yes/no/not applicable
- Infrastructure details isolated: yes/no/not applicable
- Outbox used only for real async side effects: yes/no/not applicable
- Sensitive data protected: yes/no/not applicable

Verification:
- php -l <files>: pass/fail/not run
- php bin/console lint:container: pass/fail/not run
- php ./vendor/bin/phpunit <scope>: pass/fail/not run
- composer phpstan: pass/fail/not run
- manual smoke: <endpoint/command/action/result>

Rollback:
- <exact revert/migration rollback/config rollback>
```

## 31. Common mistakes to avoid

Do not:

```text
- put business logic into controllers;
- use Doctrine EntityManager directly in application handlers;
- expose Doctrine entities in API responses;
- make domain depend on Symfony/Doctrine/Messenger;
- dispatch Symfony MessageBusInterface directly from controllers;
- route core commands to async transport;
- store raw refresh tokens;
- return refresh tokens in JSON;
- document password when API expects plainPassword;
- add outbox messages without real handlers/side effects;
- add shared abstractions before a second real use case exists;
- create Manager/Helper/Utils god classes;
- hide important state changes in Doctrine listeners;
- rely on Redis/OpenSearch/RabbitMQ as source of truth;
- use wildcard CORS with credentials;
- log credentials, tokens, cookies, or secrets.
```

## 32. Current IdentityAccess contract reminders

Auth endpoints:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
GET  /api/auth/me
```

Register/login request field:

```json
{
  "plainPassword": "..."
}
```

Auth model:

```text
access token  -> JWT returned in JSON, stored by frontend in memory
refresh token -> opaque HttpOnly cookie, never returned in JSON
```

Frontend auth requests must use credentials:

```text
fetch: credentials: 'include'
axios: withCredentials: true
```

Protected API requests use:

```http
Authorization: Bearer <accessToken>
```

## 33. Final rule

When unsure, prefer the smaller explicit design that preserves boundaries and can be tested.

Do not add abstractions, handlers, mappers, events, or shared services just because the pattern exists elsewhere. Add them when the current use case needs them and the responsibility is clear.
