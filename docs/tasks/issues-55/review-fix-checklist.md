# Review Fix Checklist: PR #59 / Issue #55

## Review comments summarized
- Expand `ProjectStatus` beyond `active` and `archived` to include `deleted`.
- Make soft-delete lifecycle rules explicit: `active -> archived`, `active -> deleted`, `archived -> deleted`, and `deleted` remains terminal.
- Persist `deleted_at`, update the DB status `CHECK`, and keep the scalar `ownerAccountId` plus FK decision intact.
- Add a real `DELETE /api/projects/{projectId}` flow with a dedicated application use case and `ProjectDeleted` domain event.
- Complete the Projects outbox chain for all emitted project events, including delete.
- Refactor the Projects controller to use the shared command/query buses, and move `ProjectNotFound` response mapping into the API exception mapper.
- Fix the create/archive API response contracts to match the requested shape and status code semantics.
- Close the migration/schema gaps and update the tests called out in review.

## Implementation checklist
- [x] Extend `ProjectStatus` to support `active`, `archived`, and `deleted`, and update the corresponding value object tests.
- [x] Make the soft-delete lifecycle explicit in the `Project` aggregate so `active -> archived`, `active -> deleted`, and `archived -> deleted` are allowed while `deleted` remains terminal.
- [x] Make `Project::archive()` repeated-call behavior explicit in the domain contract and tests, instead of leaving it implicit.
- [x] Add `deleted_at` persistence to the Projects entity/mapper/migration, and update the status `CHECK` constraint to include `deleted`.
- [x] Add the `DeleteProject` application flow, including command/query-side wiring, handler, result, repository interaction, and tests.
- [x] Add the `ProjectDeleted` domain event and emit it from the delete flow.
- [x] Add `DELETE /api/projects/{projectId}` and make it return `204 No Content`.
- [x] Refactor `ProjectController` to use `CommandBusInterface` and `QueryBusInterface` instead of calling handlers directly.
- [x] Move `ProjectNotFound` HTTP response mapping out of `ProjectController` and into the Projects API exception mapper.
- [x] Fix the create response contract to return the stable project shape with `id` instead of `projectId`, and update the OpenAPI schema/tests.
- [x] Fix the archive response contract to return `204 No Content`, and remove the archived payload body from the API contract.
- [x] Complete the Projects outbox chain for create/archive/delete: add message mappers, message classes, and message handlers for the new project events.
- [x] Fix the migration/schema gaps from review while preserving the accepted scalar `ownerAccountId` + DB FK decision and keeping `doctrine:schema:validate --skip-sync` as the mapping gate.
- [x] Add or update the domain, application, infrastructure, API, outbox, and migration tests named in review so each fix is independently verified.

## Proposed implementation order
1. Extend domain status/lifecycle rules and make `Project::archive()` behavior explicit.
2. Add `deleted_at` persistence, the status `CHECK` update, and the migration/schema corrections.
3. Implement `DeleteProject` plus `ProjectDeleted`.
4. Update the API controller, exception mapping, and response contracts.
5. Finish the outbox chain for create/archive/delete events.
6. Update the impacted tests and rerun the narrowest relevant verification first.

## Conflicts with existing issue docs/code
- `analysis.md` and `planning.md` still describe Projects as `active`/`archived` only, so they are now out of date relative to the request-changes scope.
- The current API controller still calls handlers directly and returns ad hoc 200/201 bodies, so it does not match the requested bus-based and `204` response contracts.
- The current create response schema still uses `projectId`, and the archive endpoint still returns a body, both of which conflict with the review request.
- The current migration has no `deleted_at` column and no `deleted` status constraint, so the persistence layer needs a follow-up change before the review can be satisfied.
- The review-thread fetch returned no GitHub thread records in this environment, so this checklist is based on the request-changes items provided in the task brief.

## Verification plan
- [x] `php bin/console doctrine:schema:validate --skip-sync`
- [x] Focused domain tests for `ProjectStatus` and `Project`
- [x] Focused application tests for create, archive, and delete flows
- [x] Focused Projects API functional tests for create, get, archive, and delete contracts
- [x] Focused outbox/integration tests for the new project event chain

## Resolved review blockers

- [x] Make the Projects repository contract access-aware so list/get/archive/delete use repository-level accessibility filtering instead of handler-side ownership checks.
- [x] Make the default project list return only accessible active projects.
- [x] Make get/archive/delete return non-leaky 404 behavior for deleted or inaccessible projects.
- [x] Expand the create response contract to return the full stable project shape instead of only `id` and `status`.
- [x] Remove any local `.env.test` workaround from the PR if it exists in the diff. The local `broadly_test_test` override was restored to the shared `boardly_test` URL.
- [x] Route malformed/invalid project IDs through the Projects application not-found flow so the controller no longer builds `project_not_found` JSON manually.
- [x] Keep the `projects.owner_account_id -> public.accounts(id)` FK intact while ensuring the shared test cleanup clears `projects.projects` before deleting `accounts`.
