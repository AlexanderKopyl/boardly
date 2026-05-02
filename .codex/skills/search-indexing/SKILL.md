---
name: search-indexing
description: "Design Boardly OpenSearch/Elasticsearch indexing: searchable documents, triggers, permissions-aware search, reindexing, eventual consistency, fallback behavior, and recovery."
---

# Search Indexing Skill

Use this skill when the request is about OpenSearch/Elasticsearch, search documents, filters, autocomplete, indexing, projections, stale index handling, or reindexing.

## Inputs

- Searchable feature or query requirement.
- Data that must be searchable.
- Permission/visibility constraints.
- Consistency expectations.

## Workflow

1. Identify source-of-truth data in the relational database.
2. Define search document structure.
3. Define indexing trigger.
4. Decide sync vs async indexing.
5. Define permissions and visibility filtering.
6. Define stale-index behavior.
7. Define reindexing/rebuild strategy.
8. Define fallback behavior when search is unavailable or stale.
9. Define observability signals.

## Output format

## 1. Summary

Short search/indexing interpretation.

## 2. Source of truth

State which DB records own the data.

## 3. Search document

Describe fields, denormalized data, filters, sorting, and autocomplete needs.

## 4. Indexing flow

Describe trigger, producer, consumer, and idempotency.

## 5. Permissions and visibility

Explain how hidden data is protected.

## 6. Consistency model

State what may be stale and how the system behaves.

## 7. Reindex strategy

Define full and partial reindexing.

## 8. Failure recovery

Define retries, recovery commands, and monitoring.

## 9. Risks and trade-offs

Name risks directly.

## Boardly rules

- OpenSearch/Elasticsearch is not source of truth.
- DB state wins over search index.
- Stale index must be expected.
- Reindexing must be possible.
- Search must respect permissions and issue visibility.
