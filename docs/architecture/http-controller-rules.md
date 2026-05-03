# HTTP Controller Rules

## 1. Purpose

This document defines strict HTTP controller rules for Boardly.

Boardly is an API-first Symfony backend used by a Next.js frontend. HTTP controllers are delivery adapters. They translate HTTP input into application commands/queries and translate successful application results into HTTP responses.

Controllers must stay thin. They must not become validation services, business services, exception mappers, or persistence adapters.

---

## 2. Core Flow

Default flow:

```text
HTTP Request
-> Request DTO through Symfony request mapping
-> Symfony Validator validates request DTO
-> Controller maps request DTO to Application command/query
-> Application handler executes use case
-> Centralized exception mapping handles failures
-> Controller maps successful result to response contract
```

Example:

```text
Request
-> RegisterAccountRequestDto
-> Validator
-> RegisterAccountCommand
-> RegisterAccountHandler
-> RegisterAccountResult
-> JsonResponse / Response DTO
```

---

## 3. Layer Responsibilities

### Interfaces / HTTP

May contain:

```text
- controllers
- request DTOs
- response DTOs
- presenters / response mappers
- HTTP exception subscribers / API error mappers
- transport-level validation attributes
```

Owns:

```text
- HTTP routes
- request body shape
- query parameters
- path parameters
- HTTP status codes
- response JSON shape
```

### Application

Owns:

```text
- commands and handlers
- queries and handlers
- application ports
- application exceptions
- use-case orchestration
- transaction boundary coordination
- repository calls through ports
- password hashing through ports
- domain method calls
```

### Domain

Owns:

```text
- aggregates
- entities
- value objects
- domain events
- domain exceptions
- domain services / policies when justified
```

Domain code must not know about HTTP, Symfony controllers, JSON responses, status codes, or request DTOs.

---

## 4. What Controllers May Do

Controllers may:

```text
- receive validated request DTOs
- read route parameters when needed
- map request DTOs and route parameters to application commands/queries
- call an application handler or command/query bus
- map successful application results to response DTOs or JsonResponse
- choose success HTTP status codes
```

Example:

```php
#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
public function __invoke(
    #[MapRequestPayload] RegisterAccountRequestDto $requestDto,
): JsonResponse {
    $result = ($this->handler)(new RegisterAccountCommand(
        email: $requestDto->email,
        plainPassword: $requestDto->plainPassword,
        name: $requestDto->name,
    ));

    return new JsonResponse(
        [
            'accountId' => $result->accountId(),
            'status' => $result->status(),
        ],
        JsonResponse::HTTP_CREATED,
    );
}
```

---

## 5. What Controllers Must Not Do

Controllers must not:

```text
- contain business rules
- contain domain lifecycle rules
- manually validate every request field
- manually parse JSON with json_decode() when Symfony mapping can be used
- catch and map every application/domain exception locally
- directly use Doctrine EntityManager or repository implementations
- directly persist entities
- directly call flush()
- expose Doctrine entities
- expose domain aggregates directly
- expose password hashes
- issue JWTs directly
- dispatch Messenger/RabbitMQ messages for business domain events
- perform permission decisions directly when those decisions belong to application/domain policies
```

Bad shape:

```php
public function __invoke(Request $request): JsonResponse
{
    $payload = json_decode($request->getContent(), true);

    foreach (['email', 'password', 'name'] as $field) {
        if (!isset($payload[$field])) {
            return new JsonResponse(['error' => 'missing_field'], 400);
        }
    }

    try {
        $result = ($this->handler)(...);
    } catch (EmailAlreadyRegistered $exception) {
        return new JsonResponse(['error' => 'email_already_registered'], 409);
    } catch (InvalidEmail $exception) {
        return new JsonResponse(['error' => 'invalid_email'], 400);
    }

    return new JsonResponse(...);
}
```

Problems:

```text
- manual JSON parsing
- manual field validation
- local exception mapping
- mixed responsibilities
- inconsistent API errors across controllers
```

---

## 6. Request DTO Rules

Request DTOs belong to the Interfaces / HTTP layer.

Recommended placement:

```text
src/Boardly/<Context>/Interfaces/Http/Request/<UseCase>RequestDto.php
```

Example:

```text
src/Boardly/IdentityAccess/Interfaces/Http/Request/RegisterAccountRequestDto.php
```

Request DTOs describe the HTTP contract, not the domain model.

Example:

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterAccountRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $plainPassword,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
    ) {
    }
}
```

Rules:

```text
- request DTOs are not application commands
- request DTOs are not domain value objects
- request DTOs should not contain business behavior
- request DTOs should not be passed directly into application handlers
```

---

## 7. Request Payload Mapping

Use Symfony request payload mapping for JSON request bodies:

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function __invoke(
    #[MapRequestPayload] RegisterAccountRequestDto $requestDto,
): JsonResponse {
    // ...
}
```

Declare JSON format for JSON API endpoints where appropriate:

```php
#[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
```

Avoid this by default:

```php
json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
```

Manual `json_decode()` is allowed only when there is a concrete reason Symfony request mapping cannot handle the endpoint. That exception must be justified in code review.

---

## 8. Validation Rules

There are two validation levels.

### Transport Validation

Answers:

```text
Is this HTTP request structurally valid for this endpoint?
```

Examples:

```text
- field is present
- field is not blank
- field has valid email shape
- field length is within API contract limits
- field has correct scalar type
```

Transport validation belongs to request DTOs through Symfony Validator constraints.

### Domain Validation

Answers:

```text
Is this value or operation valid according to business/domain rules?
```

Examples:

```text
- Email value object normalization and invariants
- AccountName value object invariants
- Account lifecycle transition rules
- active accounts only can authenticate
- cannot disable last active system admin
```

Domain validation belongs to value objects, aggregates, domain services, and application handlers where cross-aggregate checks are needed.

### Important

DTO validation does not replace domain validation.

Example:

```text
RegisterAccountRequestDto validates email shape.
Email value object still validates and normalizes email.
```

Application/domain code may be called from CLI, tests, workers, or future APIs without the same HTTP DTO.

---

## 9. DTO to Command Mapping

After successful DTO mapping and validation, the controller maps DTO data into an application command/query.

Example:

```php
$command = new RegisterAccountCommand(
    email: $requestDto->email,
    plainPassword: $requestDto->plainPassword,
    name: $requestDto->name,
);
```

Rules:

```text
- commands belong to Application
- request DTOs belong to Interfaces / HTTP
- do not pass request DTOs directly to handlers
- do not put Symfony Validator constraints on application commands by default
- do not let commands depend on HTTP-specific classes
```

The mapping may stay in the controller when simple. If mapping becomes repetitive or complex, introduce a mapper/presenter in the Interfaces / HTTP layer.

---

## 10. Application Handler Rules

The controller calls the application handler only after request DTO validation succeeds.

Application handlers own:

```text
- use-case orchestration
- application-level conflict checks
- permission checks through policies/ports
- repository calls through ports
- password hashing through ports
- transaction boundary through TransactionalInterface
- domain behavior calls
- outbox storage through an application port when needed
```

For registration, the handler owns:

```text
- email uniqueness check
- password hashing
- AccountId generation
- current time retrieval
- Account::register(...)
- AccountRepositoryInterface::save(...)
- application result creation
```

The controller must not duplicate these responsibilities.

---

## 11. Exception Mapping Rules

Expected application/domain exceptions must be mapped centrally, not through repetitive `try/catch` blocks in every controller.

Preferred direction:

```text
Symfony Kernel exception event
-> API exception subscriber / mapper
-> standardized JSON error response
```

Possible placement:

```text
src/Boardly/Shared/Interfaces/Http/EventSubscriber/ApiExceptionSubscriber.php
```

or context-local first:

```text
src/Boardly/IdentityAccess/Interfaces/Http/Exception/IdentityAccessApiExceptionMapper.php
```

Rules:

```text
- controllers should not catch expected application/domain exceptions locally by default
- API error response shape must be consistent across endpoints
- domain exceptions must not leak as raw class names or stack traces
- infrastructure exceptions must not be exposed directly
- unexpected exceptions are handled as internal server errors by the global error mechanism
```

Registration mapping direction:

```text
Malformed JSON / unreadable request body -> 400 invalid_json or invalid_request
Request DTO validation failure -> 422 validation_failed
EmailAlreadyRegistered -> 409 email_already_registered
Invalid domain state transition -> 409 invalid_account_status_transition
AccountNotFound where relevant -> 404 account_not_found
Unauthenticated -> 401 unauthenticated
Authenticated but forbidden -> 403 forbidden
```

Important:

```text
InvalidEmail / InvalidAccountName should normally be caught earlier by request DTO validation.
If they still appear from Domain, centralized exception mapping converts them consistently.
```

---

## 12. HTTP Status Code Policy

### 400 Bad Request

Use for malformed or unreadable transport input:

```text
- malformed JSON
- unreadable request body
- unsupported content type where content type is required
- invalid request syntax
```

### 422 Unprocessable Entity

Use when JSON/body is structurally readable but fields are invalid:

```text
- NotBlank violation
- Email violation
- Length violation
- Type violation
- invalid enum value in request DTO
```

Example:

```text
valid JSON + invalid email field -> 422
```

### 409 Conflict

Use for business conflicts:

```text
- email already registered
- invalid lifecycle transition
- cannot disable own account
- cannot disable last active system admin
- optimistic locking conflict when exposed as API conflict
```

### 401 Unauthorized

Use when authentication is missing or invalid:

```text
- missing access token
- invalid access token
- expired access token
```

### 403 Forbidden

Use when authentication exists but permission is not enough:

```text
- authenticated account cannot perform the action
- actor is not system admin for account-management operation
```

### 404 Not Found

Use when a route/use case targets a resource that does not exist:

```text
- target account does not exist
- target project does not exist
- target issue does not exist
```

---

## 13. API Error Response Shape

Suggested validation error shape:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The request payload is invalid.",
    "violations": [
      {
        "field": "email",
        "message": "This value is not a valid email address."
      }
    ]
  }
}
```

Suggested non-validation error shape:

```json
{
  "error": {
    "code": "email_already_registered",
    "message": "Email is already registered."
  }
}
```

Rules:

```text
- code is stable and machine-readable
- message is safe for client display/logging
- details must not leak sensitive data
- raw exception messages are not exposed by default
- validation errors may include field-level violations
```

---

## 14. Success Response Rules

Controllers return only API response contracts.

Allowed directions:

```text
- explicit JsonResponse array
- response DTO + presenter/normalizer
```

For simple endpoints, explicit `JsonResponse` is acceptable.

Registration success response:

```json
{
  "accountId": "7f88c85d-4ef6-4f5e-88f9-c8c4605d208c",
  "status": "pending_approval"
}
```

Rules:

```text
- do not expose Doctrine entities
- do not expose domain aggregates
- do not expose PasswordHash
- do not expose plain passwords
- do not expose refresh token hashes
- public registration must not return access tokens
- public registration must not return refresh tokens
```

---

## 15. RegisterAccountController Target Shape

```php
namespace App\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountCommand;
use App\Boardly\IdentityAccess\Application\RegisterAccount\RegisterAccountHandler;
use App\Boardly\IdentityAccess\Interfaces\Http\Request\RegisterAccountRequestDto;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RegisterAccountController
{
    public function __construct(
        private RegisterAccountHandler $handler,
    ) {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'], format: 'json')]
    public function __invoke(
        #[MapRequestPayload] RegisterAccountRequestDto $requestDto,
    ): JsonResponse {
        $result = ($this->handler)(new RegisterAccountCommand(
            email: $requestDto->email,
            plainPassword: $requestDto->plainPassword,
            name: $requestDto->name,
        ));

        return new JsonResponse(
            [
                'accountId' => $result->accountId(),
                'status' => $result->status(),
            ],
            JsonResponse::HTTP_CREATED,
        );
    }
}
```

The controller must not include:

```text
- json_decode()
- foreach field validation
- local try/catch for EmailAlreadyRegistered
- local try/catch for InvalidEmail
- local try/catch for InvalidAccountName
- EntityManager
- Doctrine repository implementation
- password hasher
- JWT issuer
- refresh token generator
```

---

## 16. Testing Rules

Controller tests should verify HTTP behavior, not re-test domain internals.

For `RegisterAccountController`, test:

```text
- malformed JSON returns 400
- missing email returns 422
- blank email returns 422
- invalid email returns 422
- blank password returns 422
- too short password returns 422
- blank name returns 422
- too long name returns 422
- duplicate email returns 409
- successful registration returns 201
- successful response contains accountId and status
- successful response does not contain passwordHash, plainPassword, accessToken, refreshToken
```

Application handler tests should verify use-case behavior.

Domain tests should verify domain behavior.

---

## 17. Common Mistakes

### Manual JSON parsing in every controller

Bad:

```php
$payload = json_decode($request->getContent(), true);
```

Problem:

```text
Every controller invents its own parsing and error behavior.
```

### Manual field validation in controller

Bad:

```php
foreach ($requiredFields as $field) {
    if (!isset($payload[$field])) {
        return new JsonResponse(...);
    }
}
```

Problem:

```text
Validation becomes inconsistent and duplicated.
```

### Passing request DTO into application handler

Bad:

```php
($this->handler)($requestDto);
```

Problem:

```text
Application layer becomes coupled to HTTP contract.
```

### Local exception mapping everywhere

Bad:

```php
try {
    // ...
} catch (EmailAlreadyRegistered $exception) {
    return new JsonResponse(...);
}
```

Problem:

```text
Every controller creates its own error contract.
```

### Returning domain aggregate from controller

Bad:

```php
return $this->json($account);
```

Problem:

```text
Domain model leaks to public API.
```

### Returning Doctrine entity from controller

Bad:

```php
return $this->json($accountEntity);
```

Problem:

```text
Persistence model leaks to public API.
```

### Treating 400 and 422 as the same

Bad:

```text
invalid JSON -> 400
valid JSON with invalid email -> 400
```

Preferred:

```text
invalid JSON -> 400
valid JSON with invalid fields -> 422
```

---

## 18. Review Checklist

Before merging a controller, check:

```text
- Does it use request DTO mapping where appropriate?
- Does it use Symfony Validator constraints for request DTO validation?
- Does it map DTO to command/query explicitly?
- Does it avoid business rules?
- Does it avoid manual field validation?
- Does it avoid local exception mapping unless justified?
- Does it avoid Doctrine EntityManager/repository implementation?
- Does it avoid returning domain aggregates?
- Does it avoid returning Doctrine entities?
- Does it return stable API response shape?
- Are 400/422/409/401/403/404 used consistently?
- Are controller tests focused on HTTP behavior?
```

---

## 19. Summary

Core flow:

```text
Request -> DTO -> Validator -> Command -> Handler -> centralized exception mapping -> Response
```

Controller responsibility:

```text
HTTP in, Application call, HTTP out.
```

Controller must not become:

```text
- parser service
- validation service
- business service
- exception mapper
- persistence adapter
- security/token service
```
