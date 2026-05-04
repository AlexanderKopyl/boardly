---
name: graphify-context
description: "Represent Boardly context as a compact knowledge graph: entities, documents, commands, agents, skills, decisions, dependencies, and risks with minimal prose."
---

# Graphify Context Skill

Use this skill to compress complex Boardly context into a compact graph of relationships.

This skill optimizes for context density and navigation, not for visual diagrams.

Do not claim a fixed token reduction multiplier. Token savings depend on the task and graph size.

## When to use

Use when:

- the task has many related documents, ADRs, agents, skills, or modules;
- the user asks for a map, graph, dependency view, or compact context;
- a feature touches several architecture concerns;
- the same relationships would otherwise require long prose;
- you need to preserve context across planning without restating full documents.

Do not use when:

- the user needs beginner-level explanation;
- the task is a simple edit;
- the graph would hide important security or consistency details;
- a normal table is clearer.

## Graph format

Use compact node and edge notation.

Prefer:

```text
[Feature:ChangeIssueStatus]
  -> reads [Design:change-issue-status]
  -> constrained_by [ADR-0003:Outbox]
  -> uses [Agent:workflow-permissions]
  -> uses [Skill:workflow-design]
  -> emits [Event:IssueStatusChanged]
```

For larger maps, group by node type:

```text
Nodes:
- Doc: ...
- ADR: ...
- Agent: ...
- Skill: ...
- Command: ...

Edges:
- Feature -> Doc
- Feature -> Agent
- Agent -> Skill
```

## Workflow

1. Identify the central node.
2. Add only directly relevant nodes.
3. Add typed edges.
4. Remove unrelated docs and agents.
5. Prefer references over copied explanations.
6. Add risks only when they affect decisions.
7. Keep prose minimal.

## Edge labels

Use clear labels:

```text
reads
constrained_by
uses
implements
emits
consumes
depends_on
blocked_by
risks
verifies
```

## Output format

## Graph

Compact node/edge map.

## Notes

Only critical clarifications.

## Missing context

Only if more context is required.

## Boardly rules

- Do not include every project document by default.
- Do not turn the whole repository into a graph unless explicitly asked.
- Keep the graph task-specific.
- DB remains source of truth when modeling persistence relationships.
- Redis, RabbitMQ, and OpenSearch are supporting infrastructure nodes, not source-of-truth nodes.
