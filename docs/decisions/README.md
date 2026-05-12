# Architecture Decision Records (ADRs)

Short, dated records of *why* a non-obvious design choice was made. The goal is to answer the question "why is it like this?" without forcing the reader to dig through git history.

## When to write an ADR

Write one **only when** all of the following are true:

1. You've made (or are about to make) a design decision that a future reader will plausibly question.
2. The decision isn't self-evident from the code.
3. You can imagine yourself or someone else explaining it twice.

If a decision is reversible in five minutes, skip the ADR. If it shapes how multiple files have to be written, write one.

## Format

Each ADR is ~30 lines. Three sections, no more:

```markdown
# NNNN — Short kebab-case title

**Date:** YYYY-MM-DD
**Status:** Accepted | Superseded by NNNN | Deprecated

## Context
What was the situation? What constraints applied? (3–5 sentences.)

## Decision
What did we decide? (2–4 sentences.)

## Consequences
What follows from this decision — good and bad? What does it force future code to do or avoid?
```

**Filename pattern:** `NNNN-kebab-case-title.md` where `NNNN` is a zero-padded sequence number, never reused.

## Index

| # | Title | Status |
|---|---|---|
| [0001](0001-quickbuilder-from-github.md) | QuickBuilder is pinned to a GitHub branch, not npm | Accepted |
| [0002](0002-singleton-getinstance.md) | All core classes use the `GetInstance` singleton trait | Accepted |
| [0003](0003-dual-frontend-builds.md) | Admin and frontend ship as separate webpack builds | Accepted |
| [0004](0004-module-gating-via-settings.md) | Extension registration is gated by a settings toggle, default-on | Accepted |

## Superseded ADRs

When a decision changes, **don't delete** the old ADR. Mark its status `Superseded by NNNN` and write a new ADR. The history is what makes ADRs useful.
