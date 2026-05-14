# Onboarding: Get current authenticated account

## Task contract

- Goal: inspect the existing application query/result DTO conventions that should guide `GetCurrentAccountQuery`.
- Non-goals: implement the query handler or result DTO.
- Constraints: keep the discovery narrow and report concrete file-backed conventions.

## Durable guidance loaded

- `AGENTS.md`
- `.codex/skills/repo-onboarding/SKILL.md`

## Candidate files

| File | Reason | Confidence |
| --- | --- | --- |
| `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php` | The query object already exists and is the direct target of the task. | High |
| `src/Shared/Application/Bus/QueryBusInterface.php` | Confirms the application-layer query bus contract. | High |
| `src/Shared/Infrastructure/Symfony/Messenger/MessengerQueryBus.php` | Shows how queries are dispatched and how handler failures are unwrapped. | Medium |
| `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountCommand.php` | Representative command DTO style in the same bounded context. | High |
| `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountResult.php` | Representative richer result DTO style in the same bounded context. | High |
| `src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminResult.php` | Representative simple application result DTO style. | High |
| `src/Boardly/IdentityAccess/Domain/Result/AccountRegistrationResult.php` | Representative domain result DTO style. | Medium |
| `tests/Boardly/IdentityAccess/Domain/Result/AccountResultTest.php` | Captures the public API expectations for result objects. | High |
| `tests/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountHandlerTest.php` | Shows how the application result DTO is consumed and asserted. | High |

## Files read

- `src/Boardly/IdentityAccess/Application/GetCurrentAccount/GetCurrentAccountQuery.php`
- `src/Shared/Application/Bus/QueryBusInterface.php`
- `src/Shared/Infrastructure/Symfony/Messenger/MessengerQueryBus.php`
- `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountCommand.php`
- `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountResult.php`
- `src/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticatedAccount.php`
- `src/Boardly/IdentityAccess/Application/CreateSystemAdmin/CreateSystemAdminResult.php`
- `src/Boardly/IdentityAccess/Application/ApproveAccount/ApproveAccountResult.php`
- `src/Boardly/IdentityAccess/Domain/Result/AccountRegistrationResult.php`
- `tests/Boardly/IdentityAccess/Domain/Result/AccountResultTest.php`
- `tests/Boardly/IdentityAccess/Application/AuthenticateAccount/AuthenticateAccountHandlerTest.php`

## Facts learned

- `GetCurrentAccountQuery` is already defined as `final readonly` with a promoted private `AccountId` property and a single `accountId()` accessor.
- Application-layer DTOs in IdentityAccess consistently use `final readonly class` plus promoted private constructor properties and public accessor methods.
- Application results are plain DTOs, not framework objects; they expose only the data the caller needs.
- `AuthenticateAccountResult` is the richest example and returns a nested `AuthenticatedAccount` DTO rather than leaking the domain model.
- `CreateSystemAdminResult` and `ApproveAccountResult` show the simpler pattern: scalar/date accessors only.
- Domain result objects follow the same DTO pattern and are asserted to expose only a minimal accessor surface.
- No `GetCurrentAccountResult` or query handler exists in the current tree.
- No other query handler pattern was found in the repository outside the query bus abstraction itself.

## Still unknown

- What exact payload `GetCurrentAccount` should return once implemented.
- Whether the query should reuse `AuthenticatedAccount` or introduce a dedicated current-account DTO.

## Recommended next skill

- `task-implementation` if the goal is to implement the query path next.
