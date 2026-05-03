---
name: feature-architecture
description: Design a Boardly feature from business behavior to Symfony implementation direction. Use for new features, workflow changes, permissions, async/search/cache impact, and MVP scoping.
---

Design the requested Boardly feature.

Project context:
- Boardly is a Jira-like project/task/workflow management system.
- Backend: Symfony 7, PHP 8.3+.
- Architecture: Modular Monolith.
- Design: DDD + Hexagonal Architecture.
- Relational DB is the source of truth.
- Redis is cache/fast storage only.
- OpenSearch/Elasticsearch is search/read-side only.
- RabbitMQ/Symfony Messenger is for async side effects, not core consistency.
- Product UI direction: API-first Symfony backend with Next.js frontend.

Rules:
- Do not assume existing files, entities, controllers, tables, queues, indexes, or module structure unless they exist.
- Do not start from Doctrine entities unless persistence design is explicitly required.
- Keep controllers thin.
- Put business rules in Domain.
- Put orchestration and transactions in Application.
- Put Doctrine/Messenger/Redis/OpenSearch adapters in Infrastructure.
- Put HTTP/API delivery concerns in Interfaces/UI.
- Always consider permissions and audit for sensitive actions.
- Important architectural decisions should become ADRs.

Expected output:
1. Summary
2. Business behavior
3. Proposed architecture
4. Domain model
5. Commands / queries / events
6. Transaction boundary
7. Sync vs async behavior
8. Symfony implementation direction
9. Permission and audit notes
10. Test strategy
11. Risks and trade-offs
12. MVP decision
13. Common mistakes
