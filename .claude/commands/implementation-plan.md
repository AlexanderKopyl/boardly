# /implementation-plan

Create a practical implementation plan for a Boardly feature or technical change.

Input:
$ARGUMENTS

Use when:
- the design is clear enough to plan code changes;
- files to inspect and change must be identified;
- tests and verification steps must be planned;
- the implementation must preserve DDD, Hexagonal Architecture, Symfony boundaries, permissions, and production safety.

Recommended agents:
- symfony-architecture;
- product-domain-analyst when feature scope is unclear;
- ddd-modeling when domain behavior is involved;
- workflow-permissions when authorization/workflow is involved;
- async-messaging when Messenger/RabbitMQ is involved;
- testing-security-reviewer before finalizing.

Recommended skills:
- feature-architecture;
- domain-modeling;
- permission-modeling;
- workflow-design;
- async-flow;
- search-indexing;
- cache-performance;
- testing-strategy;
- observability-operations.

Rules:
- Inspect repository structure before naming concrete files/classes.
- Do not invent existing entities, modules, services, or routes.
- Keep controllers thin.
- Keep business rules in Domain.
- Keep orchestration in Application.
- Keep Doctrine/Messenger/Redis/OpenSearch adapters in Infrastructure.
- Do not add dependencies, migrations, Docker/deploy changes, or destructive commands without explicit permission.

Output format:
1. Implementation plan
2. Files to inspect
3. Files to change
4. Step-by-step changes
5. Domain/Application/Infrastructure/Interfaces placement
6. Permissions/security checks
7. Async/search/cache impact
8. Tests to add/run
9. Verification steps
10. Risks/TODOs
