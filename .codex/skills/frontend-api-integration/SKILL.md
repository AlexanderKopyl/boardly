---
name: frontend-api-integration
description: "Design Boardly frontend API integration through shared HTTP client, context gateways, API contracts, error normalization, and adapter mapping without raw HTTP calls in presentation."
---

# Frontend API Integration Skill

Use this skill when the task is about HTTP clients, API contracts, gateways, backend DTO mapping, normalized errors, retry behavior, or where API calls should live.

## Source of truth

- `docs/adr/0006-use-frontend-context-based-hexagonal-architecture.md`

## Workflow

1. Identify the backend API operation.
2. Define the frontend application port that needs it.
3. Define backend API request/response contracts in infrastructure.
4. Map backend contracts to frontend domain/application models.
5. Use shared HTTP infrastructure for transport mechanics.
6. Normalize API errors outside presentation.
7. Keep raw `fetch`/Axios out of pages/components/hooks unless the hook is purely an adapter and explicitly justified.
8. Define tests for contract mapping and error behavior.

## Output format

## 1. Summary

Short integration decision.

## 2. Port

Application interface needed by the use case.

## 3. Gateway

Concrete adapter and responsibility.

## 4. API contracts

Request/response/error shapes.

## 5. Mapping

Backend contract to frontend model mapping.

## 6. Error handling

Normalized errors and UI consumption.

## 7. Tests

Gateway and mapping tests.

## Boardly rules

- Raw HTTP calls must not be scattered inside React components.
- Infrastructure maps backend API contracts to frontend models.
- Presentation must not normalize backend errors.
- Shared API code may know HTTP mechanics, but not product use cases.
