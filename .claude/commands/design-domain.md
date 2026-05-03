# /design-domain

Design the domain model for a Boardly feature or scenario.

Input:
$ARGUMENTS

Use when:
- aggregates, entities, value objects, invariants, policies, repositories, domain events, or transaction boundaries must be modeled;
- behavior is more important than Symfony wiring;
- the feature includes issue lifecycle, project membership, workflow, comments, attachments, notifications, audit, or search implications.

Recommended agents:
- ddd-modeling;
- product-domain-analyst;
- workflow-permissions when permissions or transitions affect the model;
- symfony-architecture when implementation placement is needed.

Recommended skills:
- domain-modeling;
- feature-architecture;
- permission-modeling;
- workflow-design.

Rules:
- Model behavior first, persistence second.
- Do not start from database tables.
- Do not make every entity an aggregate.
- Do not leak Symfony, Doctrine, Messenger, Redis, or OpenSearch into Domain.
- Use domain events as business facts, not technical notifications.

Output format:
1. Short answer
2. Bounded context
3. Aggregate / Entity / Value Object proposal
4. Invariants
5. Domain services / policies
6. Commands and domain events
7. Repository ports
8. Transaction boundary
9. Persistence implications
10. Common mistakes
