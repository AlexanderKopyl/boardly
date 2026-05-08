---
name: frontend-use-case-flow
description: "Design Boardly frontend application use cases, ports, orchestration, and browser-side flows without coupling use cases to React, HTTP clients, storage, or Next.js."
---

# Frontend Use Case Flow Skill

Use this skill when the task is about frontend application use cases such as login, register, refreshSession, logout, bootstrapSession, loadProjectBoard, or changeVisibleFilter.

## Workflow

1. Identify the user action or browser lifecycle event.
2. Define the frontend application use case.
3. Define required input DTOs and output/result shape.
4. Define ports/interfaces the use case depends on.
5. Keep concrete HTTP/storage adapters out of the use case.
6. Define error/result handling without leaking backend transport details.
7. Identify presentation hook/component that calls the use case.
8. Identify tests for the use case with mocked ports.

## Output format

## 1. Summary

Short flow decision.

## 2. Use case

Name, input, output.

## 3. Ports

List required interfaces.

## 4. Flow

Step-by-step orchestration.

## 5. Presentation integration

Which hook/component/page calls it.

## 6. Tests

Required unit/integration tests.

## Boardly rules

- Application use cases depend on ports, not React, Next.js, raw fetch, cookies, localStorage, or env vars.
- Use cases orchestrate frontend flows, not backend business truth.
- Backend still validates commands and permissions.
