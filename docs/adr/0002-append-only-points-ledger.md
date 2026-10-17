# 2. Append-only points ledger

- Status: Accepted
- Date: 2026-10-02

## Context

A member's point balance is the number everything else depends on: the tier,
the catalogue, redemptions and reporting. A single mutable `points_balance`
column is easy to update but impossible to audit — when a balance looks wrong
there is no way to see how it got there, and concurrent updates can silently
lose points.

## Decision

Points are stored as an **append-only** ledger in `point_transactions`. Rows are
never updated or deleted (`updated_at` is disabled); corrections are new rows.
The account's `points_balance` is a *cache* that must always equal the sum of its
ledger rows. Every balance change is written in the same transaction as the row
that updates the cache, and each row stores the resulting `balance_after`.

## Consequences

- The full history of a balance is auditable and reproducible.
- A test and the `loyalty:reconcile` command enforce the invariant
  `points_balance == SUM(points)`.
- The ledger grows without bound; reporting reads are indexed and cached
  (see ADR / Phase 6) rather than aggregated live on every request.
