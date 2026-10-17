# 5. FIFO points expiry

- Status: Accepted
- Date: 2026-10-04

## Context

Points expire twelve months after they are earned. When points are spent they
must be deducted from the correct batch, otherwise an account could keep old
points alive forever or expire points that were already spent.

## Decision

Each earning row carries `points_remaining` and `expires_at`. Spends consume the
**oldest** batches first (FIFO), decrementing `points_remaining`. A scheduled job
(`loyalty:expire-points`) expires the remaining points of batches past their
expiry date, capped at the account's current balance so expiry can never push a
balance negative. Members are notified before expiry by
`loyalty:notify-expiring-points`, which is idempotent per batch via
`reminder_sent_at`.

## Consequences

- Expiry and spend behaviour is deterministic and testable.
- The ledger stays append-only: expiry writes a new `expire` row rather than
  mutating history.
- The expiry scan is a hot query and is indexed on
  `(points_remaining, expires_at)`.
