# /design-permissions

Design authorization for a Boardly action or resource.

Input:
$ARGUMENTS

Use when:
- project roles, membership, ownership, issue visibility, voters, policies, or access checks must be designed;
- search/read models may expose protected data;
- workflow transitions depend on actor permissions;
- permission cache may be needed for a hot path.

Recommended agents:
- workflow-permissions;
- testing-security-reviewer;
- symfony-architecture;
- cache-performance when permission cache is involved;
- search-read-models when search visibility is involved.

Recommended skills:
- permission-modeling;
- workflow-design;
- cache-performance;
- search-indexing;
- testing-strategy.

Rules:
- Authorization must be server-side and explicit.
- Do not hide authorization in controllers, Doctrine listeners, or frontend logic.
- Check actor, action, resource, and context.
- Search/read models must not leak hidden issues.
- Redis may cache permissions only with TTL and safe invalidation.

Output format:
1. Short answer
2. Actor / action / resource
3. RBAC rules
4. ABAC / policy rules
5. Symfony implementation direction
6. Cache impact
7. Search/read-model visibility impact
8. Audit requirements
9. Tests
10. Abuse/data-leak risks
