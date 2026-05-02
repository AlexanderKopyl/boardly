---
name: domain-modeling
description: Model Boardly business behavior with DDD: bounded contexts, aggregates, entities, value objects, invariants, domain services, repositories, domain events, and transaction boundaries.
---

# Domain Modeling Skill

Use this skill when the request is about domain modeling, aggregate boundaries, invariants, domain events, repositories, or transaction boundaries.

## Inputs

- Business feature or scenario.
- Actor and intent, if known.
- Existing business rules, if provided.

## Workflow

1. Identify the bounded context candidate.
2. Identify the aggregate root.
3. Separate entities from value objects.
4. Define invariants that must always hold.
5. Decide what belongs inside the aggregate and what belongs in a domain service or policy.
6. Define repository ports needed by the domain/application layer.
7. Define domain events as business facts, not technical notifications.
8. Define the transaction boundary.
9. Identify consistency risks and deferred side effects.

## Output format

## 1. Summary

Short domain interpretation.

## 2. Bounded context

Name the context and why it owns the behavior.

## 3. Aggregate model

Describe aggregate root, entities, and value objects.

## 4. Invariants

List rules the model must protect.

## 5. Domain services / policies

Use only when behavior does not naturally belong inside one aggregate.

## 6. Commands and domain events

Separate user intention from business facts.

## 7. Transaction boundary

State what must be committed atomically.

## 8. Risks and trade-offs

Name modeling risks directly.

## Boardly rules

- Do not model from database tables first.
- Do not make every entity an aggregate.
- Do not leak Symfony, Doctrine, Messenger, Redis, or OpenSearch into the domain layer.
- Do not create generic managers without business meaning.
- The relational database is the source of truth.
