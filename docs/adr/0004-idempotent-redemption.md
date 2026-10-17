# 4. Idempotent reward redemption

- Status: Accepted
- Date: 2026-10-03

## Context

Redeeming a reward spends points and can decrement stock. Clients retry on
timeouts and networks duplicate requests, so a naive `POST` can charge the same
member twice for one intended action. Points are valuable, so "at least once"
delivery is unacceptable here.

## Decision

Redemption is **idempotent**. The client must send a unique `Idempotency-Key`
header. The first request performs the redemption; any replay with the same key
returns the original result (`200`) instead of spending points again. A key that
is reused for a *different* reward is a `409 Conflict` rather than a silent
success.

## Consequences

- Retries are safe and return a consistent body.
- The account and reward rows are locked (`lockForUpdate`) so concurrent
  redemptions cannot oversell stock or overspend the balance.
- Clients are responsible for generating and reusing a stable key per action;
  the header is required, and its absence is a validation error.
