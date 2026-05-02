# Account Domain Model

Status: Proposed / implementation-ready  
Date: 2026-05-02  
Issue: #6  
Related ADRs: ADR-0001, ADR-0002, ADR-0004, ADR-0005

## 1. Context

Boardly needs an initial IdentityAccess model for account registration, approval, login, refresh sessions, logout, and system-admin account management.

This document defines the first implementation direction for the `Account` domain model and the closely related `RefreshSession` model.

The goal is to keep authentication identity and account lifecycle clear without mixing project permissions, workflow rules, issue permissions, frontend state, or token persistence into the `Account` aggregate.

## 2. Scope

This document covers:

- `Account` aggregate boundary
- account lifecycle states and transitions
- account value objects
- system-admin capability
- account persistence fields
- refresh-session model boundaries
- login, refresh, and logout flows
- repository port direction
- domain events
- application result objects
- error ownership by layer
- implementation rules and anti-patterns

This document does not define:

- Symfony Security firewall configuration
- exact JWT library choice
- Doctrine mapping syntax
- controllers
- frontend implementation
- public password reset
- MFA, OAuth, or SSO
- full project authorization model

## 3. Module placement

IdentityAccess owns account identity, account lifecycle, credentials, global system-admin capability, and authentication-session lifecycle.

Initial source direction:

```text
src/Boardly/IdentityAccess/
├── Application/
│   ├── Port/
│   ├── RegisterAccount/
│   ├── Login/
│   ├── RefreshSession/
│   ├── Logout/
│   ├── GetCurrentAccount/
│   ├── ApproveAccount/
│   ├── RejectAccount/
│   └── DisableAccount/
└── Domain/
    ├── Event/
    ├── Model/
    └── ValueObject/
```

Shared business identifiers used by other bounded contexts live in the Boardly SharedKernel:

```text
src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php
```

Technical ports such as clock, ID generation, transaction handling, and outbox stay outside the domain model and follow the existing architecture rules.

## 4. Account aggregate boundary

`Account` owns only authentication identity, lifecycle, credentials, and global system-admin capability.

`Account` owns:

```text
- AccountId
- Email
- PasswordHash
- AccountName
- AccountStatus
- isSystemAdmin
- createdAt
- updatedAt
- approvedAt nullable
- rejectedAt nullable
- disabledAt nullable
```

`Account` must not own:

```text
- project memberships
- project roles
- project permissions
- issue assignments
- issue permissions
- workflow permissions
- allowed workflow transitions
- notification settings
- personal preferences
- refresh tokens/sessions
```

Authorization beyond global system-admin account-management actions is resolved through separate project/workflow/permission models and contextual policies.

Conceptual split:

```text
Account = identity + lifecycle + credentials + global system-admin capability
ProjectMembership = accountId + projectId + roleId/status
ProjectRole = configurable role inside a project/scheme
PermissionScheme = maps roles/subjects to product permissions
WorkflowScheme = maps issue type/status transitions to allowed roles/conditions
Policies = evaluate actor + action + resource + context
```

Rules:

- do not store product permissions in `Account`
- do not store project/workflow/issue permissions in JWT
- do not hardcode project role names in application code where contextual permission checks are needed
- do not let the frontend be the authorization source of truth
- do not let controllers decide business authorization directly
- use backend policies/application ports for contextual authorization

## 5. System-admin capability

First milestone uses a simple boolean global capability:

```text
isSystemAdmin: bool
```

Rules:

- system admin is global, not project-level
- project admins are not system admins
- workspace admins are not introduced yet
- account approval is not a project permission
- only system admins can approve/reject accounts in the first milestone
- do not introduce global roles/capability tables prematurely

First system admin is created through CLI/bootstrap, not public registration and not first-user auto-promotion.

Example command direction:

```bash
php bin/console boardly:identity:create-system-admin admin@example.com
```

## 6. Account status lifecycle

`AccountStatus` is a strict lifecycle state.

Initial statuses:

```text
pending_approval
active
rejected
disabled
```

Allowed transitions:

```text
pending_approval -> active
pending_approval -> rejected
rejected -> pending_approval
active -> disabled
disabled -> active
```

Forbidden transitions:

```text
rejected -> active
rejected -> disabled
active -> rejected
disabled -> rejected
disabled -> pending_approval
active -> pending_approval
```

Initial domain methods:

```php
final class Account
{
    public function approve(DateTimeImmutable $approvedAt): AccountApprovalResult;

    public function reject(DateTimeImmutable $rejectedAt): AccountRejectionResult;

    public function reopenApproval(DateTimeImmutable $reopenedAt): AccountApprovalReopenedResult;

    public function disable(DateTimeImmutable $disabledAt): AccountDisableResult;

    public function reactivate(DateTimeImmutable $reactivatedAt): AccountReactivationResult;
}
```

`reopenApproval()` and `reactivate()` can be deferred if not required by the first milestone UI/API, but the lifecycle model allows them.

Potential future route:

```text
POST /api/admin/accounts/{accountId}/reopen-approval
```

## 7. Lifecycle timestamps

`Account` stores explicit lifecycle timestamps:

```text
createdAt
updatedAt
approvedAt nullable
rejectedAt nullable
disabledAt nullable
```

Behavior:

```text
approve(): sets approvedAt, clears rejectedAt, updates updatedAt
reject(): sets rejectedAt, updates updatedAt
reopenApproval(): clears rejectedAt, updates updatedAt
disable(): sets disabledAt, updates updatedAt
reactivate(): clears disabledAt, updates updatedAt
```

Rule:

```text
AccountStatus = authoritative state
timestamps = lifecycle metadata
```

## 8. Account value objects

### 8.1 AccountId

`AccountId` is a value object in the Boardly SharedKernel because other bounded contexts reference account identity without depending on the IdentityAccess domain model.

Placement:

```text
src/Boardly/SharedKernel/Domain/ValueObject/AccountId.php
```

First implementation milestone uses UUID:

```text
AccountId wraps UUID
```

Rules:

- other contexts may reference `AccountId`
- other contexts must not load or depend on the `Account` aggregate directly
- UUID is sufficient for the first milestone
- ULID can be reconsidered later if sortable identifiers become important

### 8.2 Email

`Email` is a value object in IdentityAccess.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/Email.php
```

Rules:

- `Account` stores `Email`, not raw string
- `Email` validates basic format at construction
- `Email` normalizes before storing/comparison
- email comparison uses normalized value
- initial normalization: trim + lowercase
- `Account` does not check global uniqueness itself
- email uniqueness is enforced by repository/application flow and database unique index

Example:

```text
" User@Example.COM " -> "user@example.com"
```

### 8.3 PasswordHash

`PasswordHash` is a value object in IdentityAccess.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/PasswordHash.php
```

Rules:

- `Account` stores `PasswordHash`, not a plain password
- `Account` never receives or stores a plain password
- `Account` does not hash passwords
- `Account` does not verify plain passwords
- password hashing and verification happen through an application/infrastructure port
- Symfony PasswordHasher is used behind an adapter

Flow:

```text
plain password -> application layer -> PasswordHasherInterface -> PasswordHash -> Account
```

### 8.4 AccountName

`AccountName` is a value object in IdentityAccess.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/AccountName.php
```

Rules:

- use `AccountName`, not generic `Name`
- `Account` stores `AccountName`, not arbitrary raw string
- value is trimmed before storing/comparison
- value must not be empty
- initial maximum length is 100 characters
- used for basic profile/display purposes

## 9. Account creation flows

`Account` exposes explicit named constructors for different creation flows.

Initial constructors:

```php
final class Account
{
    public static function register(
        AccountId $id,
        Email $email,
        PasswordHash $passwordHash,
        AccountName $name,
        DateTimeImmutable $createdAt
    ): AccountRegistrationResult;

    public static function createSystemAdmin(
        AccountId $id,
        Email $email,
        PasswordHash $passwordHash,
        AccountName $name,
        DateTimeImmutable $createdAt
    ): AccountSystemAdminCreationResult;
}
```

Registration creates:

```text
status: pending_approval
isSystemAdmin: false
approvedAt: null
rejectedAt: null
disabledAt: null
```

System-admin bootstrap creates:

```text
status: active
isSystemAdmin: true
approvedAt: creation time or explicit bootstrap approval time
rejectedAt: null
disabledAt: null
```

Rules:

- avoid a generic public constructor for all creation flows
- public registration must not create active accounts
- public registration must not create system admins
- first system admin is created only by CLI/bootstrap
- constructor may be private/internal if useful

## 10. Account domain events

Account lifecycle events are included in the first milestone.

Initial events:

```text
AccountRegistered
AccountApproved
AccountRejected
AccountDisabled
```

Potential placement:

```text
src/Boardly/IdentityAccess/Domain/Event/AccountRegistered.php
src/Boardly/IdentityAccess/Domain/Event/AccountApproved.php
src/Boardly/IdentityAccess/Domain/Event/AccountRejected.php
src/Boardly/IdentityAccess/Domain/Event/AccountDisabled.php
```

Deferred/future events:

```text
AccountApprovalReopened
AccountReactivated
SystemAdminCreated
```

Rules:

- events must not contain plain passwords
- events must not contain raw refresh tokens
- events must not contain sensitive implementation details
- use transactional outbox when reliable delivery matters
- do not dispatch Messenger/RabbitMQ directly from domain models

## 11. Domain events handling style

`Account` does not internally store recorded domain events.

Preferred approach:

```text
Account lifecycle operation
-> explicit result object
-> application handler persists Account
-> application handler stores events through OutboxInterface when reliable delivery matters
```

Example result objects:

```text
AccountRegistrationResult
AccountApprovalResult
AccountRejectionResult
AccountDisableResult
```

Rejected for the first milestone:

```php
$account->recordThat(...);
$account->pullDomainEvents();
```

Reason: explicit result objects keep lifecycle outputs visible and avoid hidden mutable event buffers inside aggregates.

## 12. AccountRepositoryInterface

`AccountRepositoryInterface` lives in the IdentityAccess application layer.

Placement:

```text
src/Boardly/IdentityAccess/Application/Port/AccountRepositoryInterface.php
```

Initial interface direction:

```php
namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

interface AccountRepositoryInterface
{
    public function save(Account $account): void;

    public function get(AccountId $id): Account;

    public function find(AccountId $id): ?Account;

    public function findByEmail(Email $email): ?Account;

    public function existsByEmail(Email $email): bool;

    public function countActiveSystemAdmins(): int;
}
```

Semantics:

```text
get(AccountId $id): Account
- use when Account is required
- throws AccountNotFound when missing

find(AccountId $id): ?Account
- use when missing is an expected optional case
- returns null when missing
```

Rules:

- application handlers depend on the interface
- Doctrine implementation lives in infrastructure
- domain model does not depend on repositories
- repository methods use value objects
- email uniqueness is checked in application/repository flow and guaranteed by database unique index
- `countActiveSystemAdmins()` supports last-active-system-admin protection

## 13. Admin account-management use cases

Approve/reject/disable account use cases check actor permissions in the application layer before calling `Account` lifecycle methods.

Example approve flow:

```text
ApproveAccountCommand(actorId, targetAccountId)
-> load actor Account
-> assert actor.isSystemAdmin()
-> load target Account
-> target.approve(...)
-> save target Account
-> store produced events through OutboxInterface when reliable delivery matters
```

Rules:

- `Account::approve()` does not accept actor Account
- `Account::reject()` does not accept actor Account
- `Account::disable()` does not accept actor Account
- target Account validates only its own lifecycle transition
- application handler validates who is allowed to perform the action
- later, account-management permission checks may be extracted into `CanManageAccountsInterface`

## 14. System-admin safety rules

System admins must not be able to disable/reject their own account through normal admin actions.

Initial rule:

```text
actorId !== targetAccountId
```

Applies to:

```text
- disable account
- reject account where relevant
- future destructive/high-impact system-admin account actions
```

Additional invariant:

```text
The system should always have at least one active system admin.
```

Example disable flow:

```text
DisableAccountCommand(actorId, targetAccountId)
-> assert actorId !== targetAccountId
-> load actor Account
-> assert actor.isSystemAdmin()
-> load target Account
-> if target is system admin, ensure at least one other active system admin exists
-> target.disable(...)
-> save target Account
```

Rules:

- do not allow self-disable through normal admin flow
- do not allow the last active system admin to be disabled
- `Account` validates only its own lifecycle transition
- application/repository layer validates cross-account invariants

## 15. Account persistence fields

Initial table shape:

```text
accounts
- id uuid primary key
- email varchar unique not null
- password_hash varchar not null
- name varchar(100) not null
- status varchar not null
- is_system_admin boolean not null default false
- created_at timestamp not null
- updated_at timestamp not null
- approved_at timestamp nullable
- rejected_at timestamp nullable
- disabled_at timestamp nullable
- version int not null
```

Important constraints:

```text
- email must be unique
- password_hash must never store plain password
- status must represent AccountStatus values only
- is_system_admin is global system-admin capability for first milestone
- version is used for optimistic locking
```

Rules:

- do not store product permissions in `accounts`
- do not store project memberships in `accounts`
- do not store refresh tokens in `accounts`
- do not store frontend/session state in `accounts`
- do not rely only on application checks for email uniqueness; enforce database unique index
- use optimistic locking for conflicting account lifecycle updates

## 16. RefreshSession boundary

`RefreshSession` is not part of the `Account` aggregate.

Boundary rule:

```text
Account = identity + lifecycle + credentials + global system-admin capability
RefreshSession = authentication session/token lifecycle
```

`RefreshSession` belongs to IdentityAccess, but it is separate from `Account`.

Conceptual placement:

```text
src/Boardly/IdentityAccess/Domain/Model/RefreshSession.php
```

Repository port placement:

```text
src/Boardly/IdentityAccess/Application/Port/RefreshSessionRepositoryInterface.php
```

`RefreshSession` owns rules such as:

```text
- rotate refresh token
- detect token reuse
- revoke token family
- expire session
- track replacement token/session relationship
```

Rules:

- `Account` does not contain a `RefreshSession` collection
- `Account` does not rotate refresh tokens
- `RefreshSession` references `AccountId`
- `Account` does not reference `RefreshSession`
- session persistence uses a separate repository/table
- Symfony cookies/JWT libraries remain infrastructure concerns
- raw refresh token must not be stored; only token hash is persisted

## 17. RefreshSession value objects

### 17.1 RefreshSessionId

`RefreshSessionId` identifies one refresh session/token record.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/RefreshSessionId.php
```

### 17.2 RefreshTokenHash

`RefreshTokenHash` represents only a hashed refresh token value.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/RefreshTokenHash.php
```

Rules:

- raw refresh token is never stored in `RefreshSession`
- raw refresh token is never stored in `Account`
- raw refresh token is never logged
- raw refresh token is never emitted in domain events
- `RefreshTokenHash` must not be treated as a generic string

Flow:

```text
raw refresh token from HttpOnly cookie
-> application boundary
-> RefreshTokenHasherInterface
-> RefreshTokenHash
-> RefreshSession lookup/update
```

### 17.3 RefreshTokenFamilyId

`RefreshTokenFamilyId` identifies one refresh-token rotation chain created from one login session.

Placement:

```text
src/Boardly/IdentityAccess/Domain/ValueObject/RefreshTokenFamilyId.php
```

Example:

```text
login -> refresh token #1
refresh token #1 -> replaced by refresh token #2
refresh token #2 -> replaced by refresh token #3
```

All records in that chain share the same `RefreshTokenFamilyId`.

Difference:

```text
RefreshSessionId = one concrete refresh session/token record
RefreshTokenFamilyId = whole rotation chain
```

Rules:

- all rotated refresh tokens from one login session share the same family id
- token reuse detection revokes the whole family
- family revocation forces re-login

## 18. RefreshSessionRepositoryInterface

Initial interface direction:

```php
namespace App\Boardly\IdentityAccess\Application\Port;

use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;

interface RefreshSessionRepositoryInterface
{
    public function save(RefreshSession $session): void;

    public function get(RefreshSessionId $id): RefreshSession;

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshSession;

    public function revokeFamily(
        RefreshTokenFamilyId $familyId,
        DateTimeImmutable $revokedAt
    ): void;
}
```

Rules:

- `LoginHandler`, `RefreshSessionHandler`, and `LogoutHandler` use this repository port
- Doctrine implementation lives in infrastructure
- repository persists only token hashes, not raw refresh tokens
- family revocation is needed for reuse detection

## 19. RefreshSession lifecycle methods

`RefreshSession` exposes explicit lifecycle methods for rotation, revocation, expiration checks, and replacement checks.

Conceptual methods:

```php
final class RefreshSession
{
    public function rotate(
        RefreshSessionId $newSessionId,
        RefreshTokenHash $newTokenHash,
        DateTimeImmutable $newExpiresAt,
        DateTimeImmutable $rotatedAt
    ): RefreshSessionRotationResult;

    public function revoke(DateTimeImmutable $revokedAt): void;

    public function isExpired(DateTimeImmutable $now): bool;

    public function isRevoked(): bool;

    public function wasReplaced(): bool;
}
```

Rotation behavior:

```text
rotate() does not mutate token_hash on the current RefreshSession.
rotate() marks the current RefreshSession as replaced and returns a new RefreshSession linked to the same RefreshTokenFamilyId.
```

Result direction:

```text
RefreshSessionRotationResult contains:
- old/replaced RefreshSession
- new RefreshSession
- produced domain events if/when needed
```

Rules:

- rotated refresh tokens from one login session keep the same `RefreshTokenFamilyId`
- replaced sessions must not be usable for refresh again
- revoked sessions must not be usable for refresh
- expired sessions must not be usable for refresh
- token reuse detection revokes the whole `RefreshTokenFamilyId` chain
- do not overwrite `token_hash` on the same session record during rotation
- create a new `RefreshSession` for the new refresh token
- link old session to new session through `replacedBySessionId`

## 20. Refresh token reuse detection

If a refresh token resolves to an existing `RefreshSession` but that session was already replaced, treat it as refresh token reuse.

Flow:

```text
raw refresh token from HttpOnly cookie
-> RefreshTokenHasherInterface
-> RefreshTokenHash
-> RefreshSessionRepositoryInterface::findByTokenHash(...)
-> if session exists and session.wasReplaced()
-> revoke whole RefreshTokenFamilyId
-> reject refresh
-> require re-login
```

External API response:

```text
401 invalid_refresh_token
```

Internal meaning:

```text
This is a security-relevant token reuse event because an old rotated refresh token appeared again.
```

Rules:

- replaced refresh sessions must never be usable for refresh again
- reuse detection revokes the whole refresh token family
- family revocation invalidates the current token chain
- user must log in again after family revocation
- public error response remains neutral
- do not issue a new access token after detecting reuse
- log/audit reuse detection internally without logging raw token values

## 21. Expired refresh session handling

Expired refresh sessions are rejected, but they are not treated as refresh token reuse.

Flow:

```text
raw refresh token from HttpOnly cookie
-> RefreshTokenHasherInterface
-> RefreshTokenHash
-> RefreshSessionRepositoryInterface::findByTokenHash(...)
-> if session exists and session.isExpired(now)
-> reject refresh
-> return neutral public error
```

External API response:

```text
401 invalid_refresh_token
```

Rules:

- expired sessions must not be usable for refresh
- expired sessions must not produce a new access token
- expired sessions must not be rotated
- expiration alone does not trigger family revocation
- public response remains neutral and does not reveal exact failure reason

## 22. Revoked refresh session handling

Revoked refresh sessions are rejected, but they are not automatically treated as refresh token reuse.

Flow:

```text
raw refresh token from HttpOnly cookie
-> RefreshTokenHasherInterface
-> RefreshTokenHash
-> RefreshSessionRepositoryInterface::findByTokenHash(...)
-> if session exists and session.isRevoked()
-> reject refresh
-> return neutral public error
```

External API response:

```text
401 invalid_refresh_token
```

Rule:

```text
Revoked token is not the same as reused token.
```

Reuse detection specifically means:

```text
An already replaced refresh token appears again.
```

Rules:

- revoked sessions must not be usable for refresh
- revoked sessions must not produce a new access token
- revoked sessions must not be rotated
- revoked session alone does not automatically revoke the whole family
- family revocation may be performed by the operation that caused revocation, not by every revoked-token lookup
- public response remains neutral

## 23. Logout flow

Logout revokes the current refresh session when a matching refresh token exists and always clears the refresh cookie.

API behavior:

```text
POST /api/auth/logout -> 204 No Content
```

Flow:

```text
raw refresh token from HttpOnly cookie
-> if missing: clear cookie and return 204
-> hash token through RefreshTokenHasherInterface
-> find RefreshSession by RefreshTokenHash
-> if found: revoke current RefreshSession
-> clear refresh_token cookie
-> return 204
```

Idempotency rule:

```text
Logout is idempotent.
```

That means:

```text
- missing cookie still returns 204
- unknown token still returns 204
- already revoked session still returns 204
- cookie is always cleared
```

Rules:

- do not return 401 from logout only because refresh token is missing/invalid
- do not expose whether the refresh session existed
- do not log raw refresh token values
- do not require access token for basic logout if refresh cookie is enough for session cleanup
- always clear `refresh_token` cookie in the response

## 24. Login flow

`LoginHandler` validates account credentials and creates a new `RefreshSession` for the login session.

Flow:

```text
LoginCommand(email, plainPassword)
-> create/normalize Email value object
-> find Account by Email
-> if Account not found: invalid_credentials
-> verify password through PasswordHasherInterface
-> if password invalid: invalid_credentials
-> if Account is not active: account_not_active
-> create access JWT through JwtTokenIssuerInterface
-> create raw refresh token through OpaqueTokenGeneratorInterface
-> hash refresh token through RefreshTokenHasherInterface
-> create RefreshSession with new RefreshSessionId and RefreshTokenFamilyId
-> save RefreshSession through RefreshSessionRepositoryInterface
-> return LoginResult(accessToken, rawRefreshTokenForCookie)
```

Important token handling rule:

```text
The raw refresh token exists only long enough to be placed into the HttpOnly Secure cookie.
```

Rules:

- `Account` does not create `RefreshSession`
- `Account` does not issue JWTs
- `Account` does not generate refresh tokens
- `Account` does not hash or verify passwords
- `LoginHandler` orchestrates lookup, credential verification, token issuing, and refresh-session creation
- only active accounts can receive tokens
- pending/rejected/disabled accounts must not receive access or refresh tokens
- invalid email and invalid password both return `invalid_credentials`
- valid credentials for non-active account returns `account_not_active`
- refresh cookie is set by HTTP/controller/response adapter, not by domain model

## 25. Refresh flow

`RefreshSessionHandler` rotates the current valid refresh session and issues a new access token.

Flow:

```text
RefreshSessionCommand(rawRefreshToken)
-> hash raw refresh token through RefreshTokenHasherInterface
-> find RefreshSession by RefreshTokenHash
-> if not found: invalid_refresh_token
-> if expired: invalid_refresh_token
-> if revoked: invalid_refresh_token
-> if replaced: revoke whole RefreshTokenFamilyId + invalid_refresh_token
-> load Account by accountId
-> if Account is not active: invalid_refresh_token
-> rotate RefreshSession
-> save old replaced RefreshSession
-> save new RefreshSession
-> issue new access JWT through JwtTokenIssuerInterface
-> return RefreshSessionResult(accessToken, newRawRefreshTokenForCookie)
```

Persistence rule:

```text
Both the old replaced RefreshSession and the new RefreshSession must be persisted in one transaction.
```

Rules:

- only valid, non-expired, non-revoked, non-replaced refresh sessions can be rotated
- replaced refresh session usage triggers family revocation
- non-active accounts must not receive refreshed tokens
- public refresh failure response stays neutral
- raw refresh token is never persisted or logged
- new raw refresh token exists only long enough to be placed into HttpOnly Secure cookie
- do not issue access tokens before refresh session validation succeeds

## 26. RefreshSession events

RefreshSession domain events are deferred unless audit/security requirements make them necessary.

Deferred/optional events:

```text
RefreshSessionCreated
RefreshSessionRotated
RefreshTokenReuseDetected
RefreshSessionRevoked
```

Security/audit direction:

```text
Refresh token reuse detection may be logged/audited through application/infrastructure logging later, but it does not require first-milestone RefreshSession domain events.
```

Rules:

- RefreshSession domain events are not required in the first milestone
- reuse detection must not log raw refresh tokens
- if reliable event delivery is needed, use transactional outbox
- do not dispatch Messenger/RabbitMQ directly from domain models

## 27. Application result objects

Use explicit result objects for application use cases that need to return tokens, cookie values, identifiers, status values, or produced domain events.

Initial result objects:

```text
RegisterAccountResult(accountId, status, events)
LoginResult(accessToken, rawRefreshTokenForCookie)
RefreshSessionResult(accessToken, newRawRefreshTokenForCookie)
AccountApprovalResult(accountId, events)
AccountRejectionResult(accountId, events)
AccountDisableResult(accountId, events)
```

Void direction:

```text
Use cases that do not need to return anything meaningful may return void.
```

Rules:

- result objects are application-layer contracts
- result objects should not expose Doctrine entities
- result objects should not expose the `Account` aggregate directly to controllers
- raw refresh token may appear only in `LoginResult`/`RefreshSessionResult` for immediate HttpOnly cookie creation
- raw refresh token must not be persisted, logged, or emitted in events
- lifecycle result objects may carry produced domain events when explicit event handling/outbox storage is needed
- controllers map result objects to HTTP responses
- API response DTOs may be separate from application result objects when needed

## 28. Error model and layer ownership

Account-related errors are explicit and separated by layer responsibility.

Domain-level errors:

```text
InvalidAccountStatusTransition
AccountAlreadyActive
AccountAlreadyRejected
AccountAlreadyDisabled
AccountNotPendingApproval
```

Application-level errors:

```text
AccountNotFound
EmailAlreadyRegistered
AccountNotActive
CannotDisableOwnAccount
CannotDisableLastActiveSystemAdmin
```

Layering rule:

```text
Domain exceptions describe invalid Account lifecycle/state transitions.
Application exceptions describe use-case failures, lookup failures, uniqueness conflicts, authorization/self-management rules, and cross-account invariants.
```

Examples:

```text
Account::approve()
-> throws AccountNotPendingApproval / InvalidAccountStatusTransition when current status cannot be approved

RegisterAccountHandler
-> throws EmailAlreadyRegistered when repository/database uniqueness check fails

DisableAccountHandler
-> throws CannotDisableOwnAccount when actorId equals targetAccountId
-> throws CannotDisableLastActiveSystemAdmin when target is the last active system admin
```

Rules:

- `Account` domain model should not throw HTTP exceptions
- `Account` domain model should not know about controllers or API response codes
- application handlers translate domain/application failures into use-case exceptions
- HTTP layer maps use-case exceptions to API error responses
- do not use generic `RuntimeException` for expected business failures
- do not leak Doctrine exceptions directly to controllers

Initial API mapping direction:

```text
AccountNotFound -> 404 where target account is part of admin API
EmailAlreadyRegistered -> 409 email_already_registered
AccountNotActive -> 403 account_not_active for login with valid credentials
InvalidAccountStatusTransition / AccountNotPendingApproval -> 409 invalid_account_status_transition
CannotDisableOwnAccount -> 409 cannot_disable_own_account
CannotDisableLastActiveSystemAdmin -> 409 cannot_disable_last_active_system_admin
```

## 29. Initial application ports

Existing and expected IdentityAccess application ports:

```text
AccountRepositoryInterface
RefreshSessionRepositoryInterface
PasswordHasherInterface
JwtTokenIssuerInterface
OpaqueTokenGeneratorInterface
RefreshTokenHasherInterface
ClockInterface
IdGeneratorInterface
LoginRateLimiterInterface
```

Rules:

- application layer depends on ports
- Symfony PasswordHasher, JWT library, random token generator, Doctrine, cookies, and rate limiter are infrastructure adapters
- domain model has no dependency on Symfony, Doctrine, Messenger, Redis, OpenSearch, RabbitMQ, or HTTP

## 30. First implementation checklist

Suggested order:

```text
1. Create AccountId in SharedKernel
2. Create Email, PasswordHash, AccountName value objects
3. Create AccountStatus
4. Create Account aggregate with register/createSystemAdmin/lifecycle methods
5. Create Account result objects
6. Create Account lifecycle events
7. Create AccountRepositoryInterface
8. Create Account domain/application exceptions
9. Create RefreshSessionId, RefreshTokenHash, RefreshTokenFamilyId
10. Create RefreshSession model
11. Create RefreshSessionRotationResult
12. Create RefreshSessionRepositoryInterface
13. Implement Register/Login/Refresh/Logout handlers
14. Implement Approve/Reject/Disable handlers
15. Add Doctrine mappings and migrations
16. Add transaction boundaries where multiple writes must be atomic
17. Add API controllers and exception mapper separately
```

## 31. Common anti-patterns

Avoid:

```text
- putting project permissions into Account
- putting project memberships into Account
- putting refresh sessions into Account as a collection
- storing raw refresh tokens anywhere
- logging raw refresh tokens
- returning Account aggregate directly from controllers
- exposing Doctrine entities as API responses
- letting controllers perform account lifecycle rules
- putting Symfony Security/JWT/cookie logic into domain models
- issuing tokens for non-active accounts
- rotating expired/revoked/replaced refresh sessions
- treating every revoked token as token reuse
- using localStorage/sessionStorage for access token persistence
- storing dynamic permissions inside JWT
- dispatching RabbitMQ/Messenger directly from domain models
```

## 32. Summary

The first milestone model is:

```text
Account = identity + lifecycle + credentials + global system-admin capability
RefreshSession = refresh-token/session lifecycle
Authorization = separate contextual policies and permission models
```

The most important implementation rules are:

```text
- keep Account small and explicit
- keep refresh sessions outside Account
- persist only refresh token hashes
- rotate refresh sessions by creating new records, not overwriting old token hashes
- revoke the whole refresh-token family on reuse detection
- keep domain independent from Symfony/Doctrine/JWT/cookies
- use application handlers for orchestration and cross-account invariants
```
