# /analyze-feature

Analyze a Boardly feature request before implementation.

Input:
$ARGUMENTS

Use when:
- a new feature is requested;
- product behavior is unclear;
- MVP scope must be separated from later scope;
- business rules, actors, permissions, async behavior, search, cache, or audit impact must be identified.

Recommended agents:
- product-domain-analyst;
- symfony-architecture;
- workflow-permissions when workflow or authorization is involved;
- async-messaging when side effects are async;
- search-read-models when search/read models are involved;
- cache-performance when hot paths or Redis are involved.

Recommended skills:
- feature-architecture;
- domain-modeling;
- permission-modeling;
- workflow-design;
- testing-strategy.

Rules:
- Do not jump directly to Doctrine entities or controllers.
- Do not assume existing files/classes unless inspected.
- Keep Boardly as a Jira-like task/workflow management system, not a CRM.
- Keep DB as source of truth.
- Treat Redis, OpenSearch, and RabbitMQ as supporting infrastructure only.

Output format:
1. Short answer
2. Product behavior
3. MVP vs later scope
4. Domain/Application/Infrastructure/Interfaces split
5. Permissions/security impact
6. Async/search/cache impact
7. Tests
8. Risks and trade-offs
9. What to do next
10. Common mistakes
