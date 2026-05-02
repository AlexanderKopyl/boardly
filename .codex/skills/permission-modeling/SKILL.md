---
name: permission-modeling
description: Model Boardly permissions: RBAC, ABAC, project membership, issue visibility, ownership rules, voters/policies, cache strategy, audit-sensitive decisions, and security risks.
---

# Permission Modeling Skill

Use this skill when the request is about authorization, roles, permissions, project membership, issue visibility, ownership, voters, policies, or access-control risks.

## Inputs

- Action being performed.
- Actor.
- Target resource.
- Project membership and role, if known.
- Issue visibility or ownership rules, if known.

## Workflow

1. Identify actor, action, resource, and context.
2. Define RBAC rules.
3. Define ABAC/policy rules when role is not enough.
4. Define project membership requirements.
5. Define ownership and visibility rules.
6. Decide where the permission check belongs.
7. Define Symfony voter/policy direction.
8. Define cache strategy if the permission path is hot.
9. Define audit-sensitive decisions.
10. Identify abuse cases and data-leak risks.

## Output format

## 1. Summary

Short authorization interpretation.

## 2. Actor / action / resource

Name the security decision clearly.

## 3. RBAC rules

Define role-based rules.

## 4. ABAC / policy rules

Define state, ownership, visibility, and workflow-dependent rules.

## 5. Symfony implementation direction

Voter, policy service, Access Decision Manager, or application-level guard.

## 6. Cache strategy

Use only where justified, with TTL/invalidation.

## 7. Audit requirements

State what permission-sensitive decisions should be logged.

## 8. Abuse cases

List likely bypass and data leak scenarios.

## 9. Risks and trade-offs

Name risks directly.

## Boardly rules

- Authorization must be server-side and explicit.
- Permissions must be designed early.
- Search/read models must not leak hidden issues.
- Controllers must not contain complex authorization logic.
- Redis may cache permission decisions, but it is not source of truth.
