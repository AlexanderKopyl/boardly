# ChangeIssueStatus Design

## Status

Draft design for issue #3.

No production code is introduced by this document.

## Related Documents

- `AGENTS.md`
- `docs/architecture/project-architecture-rules.md`
- `docs/adr/0001-use-modular-monolith-hexagonal-architecture-and-ddd.md`
- `docs/adr/0002-use-boardly-context-based-source-structure.md`

## Summary

`ChangeIssueStatus` is the first deep Boardly architecture validation scenario.

The use case changes the current status of an Issue after permission and workflow checks.

Core direction:

```text
Issue owns current status.
Workflow owns statuses, workflow schemes, assignments, and transition rules.
The Issues application layer owns the ChangeIssueStatus use case.
Permission and workflow checks are represented through application ports.
The approved status mutation is applied by a domain service.
Issue persistence and outbox storage happen in one transaction.
RabbitMQ/Messenger publishing happens later through outbox infrastructure.
```

This document defines boundaries only. It does not implement commands, handlers, aggregates, repositories, mappings, migrations, controllers, Messenger handlers, OpenSearch indexers, Redis logic, or RabbitMQ consumers.

## Goals

- Define which context owns the `ChangeIssueStatus` use case.
- Define who owns current issue status.
- Define who validates workflow transition rules.
- Define how permission checks are represented.
- Define synchronous transaction boundary.
- Define domain event ownership and shape.
- Define async side effects after commit.
- Identify failure cases and open questions.
- Decide whether ADR-0003 is required before implementation.

## Non-goals

Do not implement in this task:

- PHP command class;
- command handler;
- Issue aggregate;
- Workflow aggregate;
- Project aggregate;
- Doctrine repositories;
- Doctrine mappings;
- database migrations;
- controllers;
- API endpoint shape;
- Messenger messages;
- RabbitMQ handlers;
- OpenSearch indexers;
- Redis cache logic.

## Involved Contexts

### Issues

Owns:

- `Issue`;
- `IssueType`;
- current issue status value stored on `Issue`;
- `ChangeIssueStatus` application use case;
- `IssueStatusChanged` domain event;
- status-change domain service.

Responsibilities:

```text
Issue:
- stores issue state
- stores current status
- references projectId
- references issueTypeId
- exposes a simple status mutator/setter

Issues application layer:
- loads Issue
- builds permission and workflow contexts
- calls permission port
- calls workflow transition policy port
- owns the transaction boundary
- stores events through outbox

Issues domain service:
- applies already-approved status mutation
- creates IssueStatusChanged
- returns IssueStatusChangeResult
```

### Workflow

Workflow is a separate Boardly context from the start.

Owns:

- `Workflow`;
- available statuses;
- transition rules;
- `WorkflowScheme`;
- `WorkflowAssignment` mapping `IssueTypeId -> WorkflowId`.

Responsibilities:

```text
Workflow:
- owns available statuses
- owns allowed transitions
- owns workflow scheme assignments
- validates whether a transition is allowed
```

Working model:

```text
Issue:
- issueId
- projectId
- issueTypeId
- currentStatus

Project:
- projectId
- workflowSchemeId

WorkflowScheme:
- workflowSchemeId
- assignments

WorkflowAssignment:
- issueTypeId
- workflowId

Workflow:
- workflowId
- statuses
- transitions
```

### Projects

Owns:

- `Project`;
- project configuration;
- attached `workflowSchemeId`.

Responsibilities:

```text
Projects:
- owns Project
- stores which WorkflowScheme is attached to a Project
- does not own workflow transition rules
```

`ChangeIssueStatus` should not load the full Project aggregate unless a concrete invariant requires it.

At this stage, `projectId` from Issue is enough for the use case input into permission and workflow policies.

### Identity / Access

A separate `IdentityAccess` context may be introduced later or during implementation if permission rules become concrete enough.

For this design, `ChangeIssueStatus` depends on an application-layer permission port instead of directly depending on Symfony Security, voters, or IdentityAccess internals.

## Source Placement Direction

### Application use case

```text
src/Boardly/Issues/Application/ChangeStatus/
├── ChangeIssueStatusCommand.php
└── ChangeIssueStatusHandler.php
```

### Application ports and contexts

```text
src/Boardly/Issues/Application/
├── Port/
│   ├── CanChangeIssueStatusInterface.php
│   └── WorkflowTransitionPolicyInterface.php
└── Context/
    ├── ChangeIssueStatusPermissionContext.php
    └── ChangeIssueStatusWorkflowContext.php
```

### Domain service, result model, and event

```text
src/Boardly/Issues/Domain/
├── Service/
│   └── IssueStatusChanger.php
├── Model/
│   └── IssueStatusChangeResult.php
└── Event/
    └── IssueStatusChanged.php
```

### Shared Boardly domain event contract

```text
src/Boardly/SharedKernel/Domain/Event/DomainEvent.php
```

### Shared technical application ports

```text
src/Shared/Application/Outbox/OutboxInterface.php
src/Shared/Application/Transaction/TransactionalSessionInterface.php
src/Shared/Application/Clock/ClockInterface.php
src/Shared/Application/Id/IdGeneratorInterface.php
```

## Application Command Shape

`ChangeIssueStatusCommand` represents external user intent.

Conceptual fields:

```text
ChangeIssueStatusCommand:
- actorId
- issueId
- targetStatus
```

Rules:

```text
- The command must not contain the Issue entity.
- actorId is operation context, not Issue state.
- issueId is required to load Issue.
- projectId, issueTypeId, and currentStatus are read from the loaded Issue.
```

## Permission Check Approach

Use an application-layer permission port in the Issues context.

Port:

```text
src/Boardly/Issues/Application/Port/CanChangeIssueStatusInterface.php
```

Conceptual API:

```text
CanChangeIssueStatusInterface::assertCanChange(ChangeIssueStatusPermissionContext $context): void
```

The method returns normally if allowed and throws if denied.

Context:

```text
ChangeIssueStatusPermissionContext:
- actorId
- projectId
- issueId
- issueTypeId
- fromStatus
- toStatus
```

Rules:

```text
- Do not pass a long scalar argument list to the permission checker.
- Do not put permission DTO/context objects in the same folder as application ports.
- Do not put permission logic in the controller.
- Do not put Symfony Security directly into the domain model.
```

## Workflow Validation Approach

Use an application-layer workflow policy port in the Issues context.

Port:

```text
src/Boardly/Issues/Application/Port/WorkflowTransitionPolicyInterface.php
```

Conceptual API:

```text
WorkflowTransitionPolicyInterface::assertCanTransition(ChangeIssueStatusWorkflowContext $context): void
```

The method returns normally if transition is allowed and throws if not allowed.

Context:

```text
ChangeIssueStatusWorkflowContext:
- projectId
- issueTypeId
- fromStatus
- toStatus
```

Rules:

```text
- Workflow owns transition rules.
- Issue does not validate workflow transitions.
- Issue does not own available statuses.
- Handler should not know workflow internals.
- Do not return transition objects from a validate/assert method.
```

If transition metadata is needed later, introduce a separate resolver, for example:

```text
WorkflowTransitionResolverInterface::resolveTransition(...): WorkflowTransitionDefinition
```

Do not hide metadata resolution inside `assertCanTransition`.

## Domain Behavior Ownership

### Issue

`Issue` owns state only.

Responsibilities:

```text
Issue:
- stores current status
- exposes a simple status mutator/setter
- does not decide permissions
- does not resolve workflow
- does not validate transition rules
- does not dispatch events
- does not record events internally
```

### IssueStatusChanger

`IssueStatusChanger` lives in:

```text
src/Boardly/Issues/Domain/Service/IssueStatusChanger.php
```

Responsibilities:

```text
IssueStatusChanger:
- receives an already-approved status change
- captures previousStatus
- applies targetStatus through Issue's simple mutator
- creates IssueStatusChanged
- returns IssueStatusChangeResult
- does not load repositories
- does not manage transactions
- does not know Symfony Security
- does not validate workflow rules
- does not know outbox
- does not dispatch Messenger/RabbitMQ messages
```

Conceptual shape:

```text
IssueStatusChanger.change(issue, targetStatus, actorId, eventId, occurredAt): IssueStatusChangeResult
```

### IssueStatusChangeResult

`IssueStatusChangeResult` lives in:

```text
src/Boardly/Issues/Domain/Model/IssueStatusChangeResult.php
```

Conceptual fields:

```text
IssueStatusChangeResult:
- issueId
- previousStatus
- newStatus
- events: DomainEvent[]
```

Rules:

```text
- Handler does not manually create IssueStatusChanged.
- Issue does not keep an internal event collection.
- IssueStatusChanger creates the event because it performs the state mutation.
- IssueStatusChanger returns the created event through IssueStatusChangeResult.
```

## Domain Event

Concrete event:

```text
src/Boardly/Issues/Domain/Event/IssueStatusChanged.php
```

Contract:

```text
src/Boardly/SharedKernel/Domain/Event/DomainEvent.php
```

`IssueStatusChanged` implements `DomainEvent`.

Initial fields:

```text
IssueStatusChanged:
- eventId
- issueId
- actorId
- fromStatus
- toStatus
- occurredAt
```

Fields intentionally excluded for now:

```text
- projectId
- issueTypeId
```

Reason:

```text
The event should capture the core business fact: actor changed issue status from one status to another at a specific time.
projectId and issueTypeId can be resolved through issueId if a concrete consumer needs them.
Do not add denormalized fields speculatively.
```

If search indexing, audit, notification routing, or analytics later require `projectId` or `issueTypeId` without extra reads, extend the event or outbox payload deliberately.

## eventId and occurredAt

`IssueStatusChanger` does not generate `eventId` and does not read current time itself.

The application layer provides both values.

Shared technical ports:

```text
src/Shared/Application/Clock/ClockInterface.php
src/Shared/Application/Id/IdGeneratorInterface.php
```

Responsibilities:

```text
Application layer:
- obtains/generates eventId
- obtains occurredAt from ClockInterface
- passes both into IssueStatusChanger

IssueStatusChanger:
- receives ready eventId and occurredAt
- creates IssueStatusChanged using those values
```

## Transaction Boundary

`ChangeIssueStatusHandler` owns the application transaction boundary.

Shared port:

```text
src/Shared/Application/Transaction/TransactionalSessionInterface.php
```

Conceptual API:

```text
TransactionalSessionInterface::execute(callable $operation): mixed
```

Transaction rules:

```text
- Repositories do not open use-case transactions.
- Domain services do not manage transactions.
- Issue save and outbox store must be atomic.
- RabbitMQ/Messenger publishing happens later, outside the transaction.
```

Status mutation happens inside the transaction.

Conceptual flow:

```text
1. Handler loads Issue.
2. Handler builds permission context from command and Issue state.
3. Handler asserts permission.
4. Handler builds workflow context from Issue state and target status.
5. Handler asserts workflow transition policy.
6. Handler obtains eventId and occurredAt.
7. Handler opens transaction through TransactionalSessionInterface.
8. Inside transaction:
   - IssueStatusChanger changes Issue status.
   - Issue repository saves Issue.
   - Outbox stores IssueStatusChanged event(s).
9. Transaction commits.
10. OutboxPublisher later publishes stored events to Messenger/RabbitMQ.
```

Permission and workflow checks may happen before opening the transaction if no DB lock is required.
Optimistic locking protects against concurrent changes between read/validation and save.

## Outbox

Use transactional outbox for domain event delivery.

Shared application port:

```text
src/Shared/Application/Outbox/OutboxInterface.php
```

Conceptual API:

```text
OutboxInterface::store(array $events): void
```

Where:

```text
$events = DomainEvent[]
```

Rules:

```text
- Domain events are stored durably in the same DB transaction as the business state change.
- Outbox storage is synchronous.
- Outbox publishing is asynchronous.
- Domain services do not dispatch events.
- Command handlers do not directly publish RabbitMQ/Messenger messages as part of the business mutation.
- RabbitMQ/Messenger availability must not be required for the status change to be valid.
```

Outbox infrastructure later publishes stored events to Messenger/RabbitMQ and handles retries/deduplication.

## Optimistic Locking

Use optimistic locking on `Issue` version.

Conceptual model:

```text
Issue:
- issueId
- status
- version
```

Expected concurrent behavior:

```text
User A reads: Todo
User B reads: Todo
User A saves: Todo -> In Progress
User B tries: Todo -> Done
```

Expected result:

```text
Only one write succeeds.
The second write fails with a conflict because Issue version changed.
```

Rules:

```text
- No last-write-wins for Issue status changes.
- Do not silently retry the command.
- Caller can reload Issue and retry intentionally if needed.
```

Infrastructure must translate Doctrine optimistic locking exceptions into a Boardly-level conflict exception.

Conceptual mapping:

```text
Doctrine\ORM\OptimisticLockException
    -> IssueStatusChangeConflict
```

Do not leak Doctrine exceptions outside infrastructure.

Possible mapping location:

```text
Issues/Infrastructure/Persistence/Doctrine/DoctrineIssueRepository
```

or, if flush is centralized:

```text
Shared/Infrastructure/Doctrine/DoctrineTransactionalSession
```

## Handler Result

`ChangeIssueStatusHandler` returns `void`.

Reason:

```text
ChangeIssueStatus is a command use case.
It changes state and does not need to return the updated Issue aggregate.
```

Rules:

```text
- Do not return Issue entity from the command handler.
- Do not build API response DTOs inside the command handler.
- If the client needs updated issue representation, it should use a separate query/read model request.
```

## Happy Path

```text
1. A caller submits ChangeIssueStatusCommand(actorId, issueId, targetStatus).
2. Handler loads Issue by issueId.
3. Handler reads projectId, issueTypeId, and currentStatus from Issue.
4. Handler builds ChangeIssueStatusPermissionContext.
5. CanChangeIssueStatusInterface.assertCanChange(...) passes.
6. Handler builds ChangeIssueStatusWorkflowContext.
7. WorkflowTransitionPolicyInterface.assertCanTransition(...) passes.
8. Handler obtains eventId from IdGeneratorInterface.
9. Handler obtains occurredAt from ClockInterface.
10. Handler starts TransactionalSessionInterface.execute(...).
11. Inside the transaction, IssueStatusChanger applies the approved status change.
12. IssueStatusChanger returns IssueStatusChangeResult containing IssueStatusChanged.
13. Handler saves Issue.
14. Handler stores returned events in outbox.
15. Transaction commits.
16. Handler returns void.
17. Transport layer may map successful command completion to 204 No Content.
18. OutboxPublisher later publishes IssueStatusChanged to Messenger/RabbitMQ.
19. Async consumers update search, audit/activity, notifications, board/read models, and reporting projections.
```

## Failure Cases

### Issue not found

Condition:

```text
issueId does not identify an existing Issue.
```

Behavior:

```text
- Command fails.
- No status mutation.
- No outbox event.
- API-facing mapping: 404 Not Found.
```

### Actor is not authenticated

Condition:

```text
actorId is missing or cannot be resolved.
```

Behavior:

```text
- Command is not executed.
- No status mutation.
- No outbox event.
- API-facing mapping: 401 Unauthorized.
```

### Permission denied

Condition:

```text
CanChangeIssueStatusInterface.assertCanChange(...) fails.
```

Behavior:

```text
- No status mutation.
- No outbox event.
- API-facing mapping: 403 Forbidden.
```

### Unknown target status

Condition:

```text
targetStatus is not a valid status for the resolved workflow.
```

Behavior:

```text
- No status mutation.
- No outbox event.
- API-facing mapping: 422 Unprocessable Entity.
- Error code: unknown_issue_status.
```

### Invalid workflow transition

Condition:

```text
targetStatus exists, but transition from currentStatus to targetStatus is not allowed by workflow rules.
```

Behavior:

```text
- No status mutation.
- No outbox event.
- API-facing mapping: 409 Conflict.
- Error code: invalid_workflow_transition.
```

### Missing workflow scheme / workflow assignment

Condition:

```text
Project workflow scheme is missing, or no assignment exists for issueTypeId.
```

Behavior:

```text
- No status mutation.
- No outbox event.
- Treat as workflow configuration conflict.
- API-facing mapping: 409 Conflict unless later classified differently.
```

### Optimistic locking conflict

Condition:

```text
Issue version changed between loading and saving.
```

Behavior:

```text
- Transaction rolls back.
- Outbox event is not committed.
- No silent retry.
- API-facing mapping: 409 Conflict.
- Error code: issue_status_change_conflict.
```

### Persistence or outbox storage failure

Condition:

```text
Issue save fails or outbox storage fails inside transaction.
```

Behavior:

```text
- Transaction rolls back.
- Status change is not committed.
- Outbox event is not committed.
- API-facing mapping: 500 Internal Server Error or infrastructure-specific error mapping.
```

### Outbox publishing failure

Condition:

```text
OutboxPublisher fails to publish stored event to Messenger/RabbitMQ after commit.
```

Behavior:

```text
- Issue status change remains committed.
- Outbox record remains retryable.
- Side effects are retried by outbox/Messenger infrastructure.
```

### Async consumer failure

Condition:

```text
Search indexing, audit projection, notification, board read model, or reporting consumer fails.
```

Behavior:

```text
- Issue status change remains committed.
- Failed side effect is retried.
- Consumers must be idempotent.
```

## API-facing Error Mapping Notes

This document does not define endpoint structure or controller implementation.

Minimal API-facing mapping accepted for failure documentation:

```text
Success -> 204 No Content
Issue not found -> 404 Not Found
Unauthenticated actor -> 401 Unauthorized
Permission denied -> 403 Forbidden
Unknown targetStatus -> 422 Unprocessable Entity
Invalid workflow transition -> 409 Conflict
Optimistic locking conflict -> 409 Conflict
```

Use HTTP status for the broad category and application error code for the exact business reason.

Endpoint shape, request body format, and controller class placement are deferred.

## Async Side Effects

After `IssueStatusChanged` is published from outbox to Messenger/RabbitMQ, async consumers may handle:

```text
- update search index
- create audit/activity entry or projection
- send notifications
- update board/read model if needed
- update reporting projection if needed
```

Rules:

```text
- These side effects are not part of the synchronous ChangeIssueStatus transaction.
- Failed async side effects must not invalidate already committed core state.
- Consumers must be idempotent.
- Search/read models may be eventually consistent.
- Database remains the source of truth.
```

## Domain Event vs Integration Event

Current decision:

```text
Domain event: IssueStatusChanged
Integration event: not introduced yet
```

Reason:

```text
Boardly is currently a modular monolith.
IssueStatusChanged is needed internally for search indexing, audit/activity projections, notifications, board/read-model updates, and reporting projections.
```

Future open question:

```text
When Boardly introduces external integrations or extracts independent services, define a stable versioned integration event such as IssueStatusChangedV1.
```

Distinction:

```text
Domain event:
- internal business fact
- owned by Issues context
- may evolve with the domain model

Integration event:
- stable public contract
- versioned
- designed for external consumers or independently deployed services
```

## ADR-0003 Decision

ADR-0003 is required before implementation.

Proposed title:

```text
ADR-0003: Use transactional outbox for domain event delivery
```

Reason:

```text
Transactional outbox is an architectural decision that affects the whole Boardly system, not only ChangeIssueStatus.
```

Impacted areas:

```text
- Issues
- Projects
- Workflow
- Notifications
- Audit/activity projections
- Reporting projections
- Search indexing
- future integrations
```

Expected file:

```text
docs/adr/0003-use-transactional-outbox-for-domain-event-delivery.md
```

## Open Questions

### Same-status transition

Not decided yet:

```text
What happens if targetStatus equals currentStatus?
```

Possible options:

```text
- treat as idempotent success with no event
- reject as invalid transition
- allow only if workflow explicitly defines self-transition
```

### Exact permission implementation

Not decided yet:

```text
Will CanChangeIssueStatusInterface be implemented through IdentityAccess, Symfony Security voters, a custom policy, or a combination?
```

Current rule:

```text
ChangeIssueStatusHandler depends only on the application port.
```

### Actor identifier naming

Not decided yet:

```text
Should the domain use ActorId or UserId for the performer of the operation?
```

Current design uses `actorId` conceptually because the value represents the performer of the command.

### Repository contract placement

Not decided yet:

```text
Where exactly should the Issue repository contract live?
```

Candidate options:

```text
- Issues/Domain/Repository if it expresses aggregate collection semantics
- Issues/Application/Port if it is purely use-case persistence port
```

### Workflow policy implementation details

Not decided yet:

```text
How exactly WorkflowTransitionPolicyInterface resolves project workflowSchemeId and workflow assignment.
```

Current rule:

```text
Handler passes projectId, issueTypeId, fromStatus, and toStatus.
The policy hides Workflow/Project resolution details from the use case.
```

### Transition metadata

Not decided yet:

```text
Whether transitions need metadata such as transitionId, required fields, resolution, comments, or post-functions.
```

Current design keeps command shape minimal.

### Doctrine mapping strategy

Not decided yet:

```text
Doctrine attributes vs XML vs PHP mapping for domain entities.
```

This should be decided before implementing persistent aggregates.

### API endpoint design

Not part of issue #3.

Deferred:

```text
- endpoint path
- HTTP method
- request body structure
- controller class placement
- response DTO shape
```

## Review Checklist

- [x] Happy path is defined.
- [x] Failure cases are listed.
- [x] Involved contexts are defined.
- [x] Application use case placement is defined.
- [x] Current issue status ownership is defined.
- [x] Workflow transition validation ownership is defined.
- [x] Permission check approach is defined.
- [x] Transaction boundary is defined.
- [x] Domain event ownership and fields are defined.
- [x] Async side effects are defined.
- [x] Open questions are listed.
- [x] ADR-0003 need is stated.
