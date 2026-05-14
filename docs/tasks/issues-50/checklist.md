# Implementation Checklist: GET /api/auth/me Current Account Endpoint

- [x] Step 1: Confirm the working tree and baseline IdentityAccess tests before coding; stop if unrelated failures obscure issue #50.
- [x] Step 2: Add `GetCurrentAccountQuery` with an `AccountId` input created from the authenticated principal/account id.
- [x] Step 3: Add `GetCurrentAccountResult` exposing only `id`, `email`, `name`, and `status` as strings.
- [x] Step 4: Add `GetCurrentAccountHandler` that loads the account through `AccountRepositoryInterface` by id and maps the account into `GetCurrentAccountResult`.
- [x] Step 5: Define controlled missing-account and non-active-account behavior in the handler using the current standardized auth failure policy; do not allow an uncaught `AccountNotFound` to become a 500.
- [x] Step 6: Register the query handler on `query.bus` if existing Symfony Messenger configuration does not autowire it automatically.
- [x] Step 7: Add `GetCurrentAccountController` under `Interfaces/Http/Controller/Auth` with `#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'], format: 'json')]`.
- [x] Step 8: In the controller, obtain the authenticated user through the existing security principal style, guard that it is `AuthenticatedAccountUser`, create `GetCurrentAccountQuery` from `accountId()`, call `QueryBusInterface::ask()`, and map the result to `JsonResponse`.
- [x] Step 9: Add OpenAPI attributes/schema for `GET /api/auth/me` using the existing issue #46 OpenAPI conventions and `bearerAuth`; document 200 and 401 responses.
- [x] Step 10: Add application tests for successful active account lookup, missing account controlled failure, and non-active account rejection if the use case performs the active check.
- [x] Step 11: Add HTTP/security tests for missing bearer token, invalid token, expired token, token for missing account, token for non-active account, valid active account, and sensitive-field absence.
- [ ] Step 12: Run focused syntax, routing, firewall, PHPUnit, OpenAPI/container, and lint checks listed in the plan.
- [ ] Step 13: Update implementation notes with exact behavior decisions, especially missing-account and non-active-account mapping.
