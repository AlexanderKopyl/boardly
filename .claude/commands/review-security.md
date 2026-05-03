# /review-security

Review a Boardly feature, design, diff, or implementation for authorization and security risks.

Input:
$ARGUMENTS

Use when:
- a feature changes access to projects, issues, comments, attachments, boards, search, reports, or audit data;
- voters, policies, firewalls, roles, membership, or visibility rules are involved;
- search/read models may expose protected data;
- async handlers may process sensitive data.

Recommended agents:
- testing-security-reviewer;
- workflow-permissions;
- symfony-architecture;
- search-read-models when search/read models are involved;
- cache-performance when permission cache is involved.

Recommended skills:
- permission-modeling;
- testing-strategy;
- search-indexing;
- cache-performance.

Rules:
- Check actor, action, resource, and context.
- Verify authorization is server-side and explicit.
- Verify project membership and issue visibility are enforced.
- Verify search/read models do not leak hidden data.
- Verify audit-sensitive mutations are logged.
- Do not approve complex authorization hidden in controllers, Doctrine listeners, or frontend logic.

Output format:
1. Summary
2. Critical issues
3. Authorization issues
4. Visibility/data exposure risks
5. Audit gaps
6. Async/search/cache security risks
7. Tests missing
8. Recommended fixes
9. Acceptable as-is
