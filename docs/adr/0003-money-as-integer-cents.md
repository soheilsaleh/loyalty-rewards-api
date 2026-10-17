# 3. Money as integer cents

- Status: Accepted
- Date: 2026-10-02

## Context

Purchases and reports deal with money that is later converted into points. Storing
monetary amounts as floating-point numbers (`amount`, `DECIMAL`) invites rounding
errors that compound across millions of rows and make exact tests flaky.

## Decision

All monetary amounts are stored and transported as **integer cents** in columns
and fields named `amount_cents`. Points are integers too. The only place a
decimal value exists is a formatted display, which is derived from the integer.

## Consequences

- Additions and comparisons are exact; tests can assert equality safely.
- The earning formula (`points = floor(amount_cents * rate / 100)`) is
  deterministic and easy to test with datasets.
- API consumers must send and read cents; this is documented in the OpenAPI spec
  and the field name makes the unit explicit.
