# /design-search-indexing

Design Boardly search indexing, projections, and read models.

Input:
$ARGUMENTS

Use when:
- OpenSearch/Elasticsearch documents must be designed;
- search filters, sorting, autocomplete, dashboards, boards, reports, or projections are involved;
- permissions-aware search is required;
- stale-index behavior, reindexing, or recovery must be planned.

Recommended agents:
- search-read-models;
- async-messaging when indexing is async;
- workflow-permissions when visibility rules are involved;
- cache-performance when read paths are hot;
- devops-adr-documentation when reindexing/recovery is required.

Recommended skills:
- search-indexing;
- async-flow;
- permission-modeling;
- cache-performance;
- observability-operations;
- testing-strategy.

Rules:
- OpenSearch/Elasticsearch is not source of truth.
- DB state wins over search index.
- Stale index must be expected.
- Search must respect project membership and issue visibility.
- Reindexing and recovery commands must be planned.

Output format:
1. Short answer
2. Source of truth
3. Search/read model document
4. Indexing/projection flow
5. Permission and visibility filtering
6. Consistency model
7. Reindex/recovery strategy
8. Cache/performance impact
9. Tests
10. Risks and trade-offs
