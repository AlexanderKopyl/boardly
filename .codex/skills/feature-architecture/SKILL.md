---
name: feature-architecture
description: "Design an end-to-end Boardly feature architecture: business context, application flow, domain model, ports/adapters, sync/async boundaries, commands, queries, events, and MVP decision."
---

# Feature Architecture Skill

Use this skill when the request is about designing a feature such as `CreateIssue`, `AssignIssue`, `ChangeIssueStatus`, `CommentOnIssue`, `SearchIssues`, or `GetProjectBoard`.

## Inputs

- Feature name.
- Business goal.
- Actor and permissions, if known.
- Expected synchronous behavior.
- Side effects, if known.

## Workflow

1. Clarify the business behavior.
2. Identify the bounded context that owns the feature.
3. Define command/query entry points.
4. Define the application service or handler responsibility.
5. Identify domain model changes.
6. Identify ports and infrastructure adapters.
7. Separate synchronous state changes from asynchronous side effects.
8. Decide what belongs in MVP and what should wait.
9. Identify testing and operational risks.

## Output format

## 1. Summary

Short architectural interpretation.

## 2. Business context

What business process drives the feature.

## 3. Proposed architecture

Bounded context, aggregate, application service, ports/adapters, sync/async boundaries.

## 4. Main components

| Component | Responsibility |
| --- | --- |
| Example | Example responsibility |

## 5. Commands / queries / events

Separate commands, queries, domain events, and integration events.

## 6. Consistency and transactions

What must happen in one DB transaction and what can be eventually consistent.

## 7. Search / cache / async notes

Use OpenSearch, Redis, and RabbitMQ only where justified.

## 8. Design patterns used

Mention only justified patterns and trade-offs.

## 9. Symfony implementation direction

Mention Symfony mechanisms as implementation hints only.

## 10. Risks and trade-offs

Name risks directly.

## 11. Recommended MVP decision

Say what should be implemented now and what should wait.

## Boardly rules

- Business behavior first, Symfony mechanics second.
- Controllers must stay thin.
- DB is source of truth.
- Redis/OpenSearch/RabbitMQ must not own core state.
