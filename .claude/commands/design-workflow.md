# /design-workflow

Design a Boardly issue workflow or status transition.

Input:
$ARGUMENTS

Use when:
- issue statuses or transitions must be defined;
- transition guards or validators are needed;
- Symfony Workflow Component fit must be evaluated;
- audit, permissions, async side effects, or search updates are affected by status changes.

Recommended agents:
- workflow-permissions;
- ddd-modeling;
- symfony-architecture;
- testing-security-reviewer.

Recommended skills:
- workflow-design;
- permission-modeling;
- domain-modeling;
- testing-strategy.

Rules:
- Workflow logic must be server-side.
- Workflow logic must not live in controllers or frontend only.
- Separate permission guards from business validators.
- Validate transitions synchronously before committing state.
- Emit async side effects only after the core state change is committed.

Output format:
1. Short answer
2. Statuses and business meaning
3. Allowed transitions
4. Guards and validators
5. Permissions
6. Symfony Workflow Component fit
7. Audit requirements
8. Async/search/cache impact
9. Tests
10. Common mistakes
