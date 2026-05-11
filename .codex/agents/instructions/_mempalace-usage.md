# MemPalace Usage Rules for Boardly Agents

## Core rule

Use MemPalace only when remembered context can materially change the decision.

Do not use MemPalace for simple repository discovery.

## Use MemPalace when memory can change the answer

Use MemPalace for:

- previous DEV ticket context;
- architectural decisions;
- why a previous approach was selected or rejected;
- known risks and regressions;
- cross-feature context;
- business logic already discussed;
- repeated integration nuances;
- historical constraints that are not obvious from the current repository files.

## Good query examples

```text
GA4 purchase Measurement Protocol checkout confirm
Apple Pay merchant validation LiqPay
product of day validation date range
ESputnik discount order product
Boardly frontend ADR-0006 identity access auth session
Boardly ChangeIssueStatus workflow permission audit
```

## Do not use MemPalace for simple repo discovery

Do not use MemPalace to answer:

- where a class is located;
- which route calls a controller;
- where a payload is built;
- where `dataLayer.push` is called;
- which files are connected to a flow;
- what changed in the current Git diff;
- what file names exist in the repository.

For those, inspect the repository directly.

## How to use

Before using MemPalace, ask:

```text
Could remembered context change the architecture, business rule, risk assessment, or implementation choice?
```

If yes, query MemPalace.

If no, use repository search, file inspection, Git diff, or local commands.

## Safety

- Do not use MemPalace to bypass repository evidence.
- Do not treat memory as more authoritative than current code or accepted ADRs.
- If memory conflicts with current ADRs or code, report the conflict explicitly.
- Do not store or retrieve secrets, SQL dumps, private keys, or sensitive environment values.
