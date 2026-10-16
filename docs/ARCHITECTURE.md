# Architecture

This document describes how the Loyalty & Rewards API is structured and how a
request flows through it. It is written for someone reviewing the codebase for
the first time.

## Layered design

Controllers stay thin: they validate the request, delegate to a service, and
shape the response with an API Resource. Business rules live in services so they
can be unit-tested without HTTP.

```
HTTP request
    │
    ▼
FormRequest ──► Controller ──► Service ──► Models ──► MySQL
                    │             │
                    │             ├── DB::transaction when >1 table is touched
                    │             └── Redis (cache / locks / queue)
                    ▼
               API Resource ──► JSON response
```

## Core invariants

1. `loyalty_accounts.points_balance` equals the sum of `points` over that
   account's `point_transactions` rows. Enforced by a test and by the
   `loyalty:reconcile` Artisan command.
2. `point_transactions` is **append-only**: rows are inserted, never updated or
   deleted, so the history is always auditable.
3. Money is stored as integer **cents** (`amount_cents`), never floats.

## Purchase to ledger

```mermaid
sequenceDiagram
    participant Staff
    participant API
    participant PointsService
    participant DB
    Staff->>API: POST /api/v1/purchases
    API->>PointsService: earn(account, purchase)
    PointsService->>DB: BEGIN
    PointsService->>DB: INSERT point_transactions (earn)
    PointsService->>DB: UPDATE loyalty_accounts.balance/lifetime
    PointsService->>DB: evaluate tier upgrade
    PointsService->>DB: COMMIT
    API-->>Staff: 201 Created
```

## Reward redemption

```mermaid
sequenceDiagram
    participant Customer
    participant API
    participant RedemptionService
    participant DB
    Customer->>API: POST /rewards/{id}/redeem (Idempotency-Key)
    API->>RedemptionService: redeem(account, reward, key)
    RedemptionService->>DB: BEGIN
    RedemptionService->>DB: SELECT ... FOR UPDATE (account, reward)
    RedemptionService->>DB: check balance, stock, idempotency
    RedemptionService->>DB: INSERT point_transactions (redeem)
    RedemptionService->>DB: INSERT redemptions (unique code)
    RedemptionService->>DB: COMMIT
    API-->>Customer: 201 Created { code }
```

## Directory conventions

| Path                     | Responsibility                                  |
| ------------------------ | ----------------------------------------------- |
| `app/Enums`              | Typed enums instead of magic strings             |
| `app/Services`           | Domain logic and transactions                    |
| `app/Jobs`               | Queued work (points awarding, expiry)            |
| `app/Policies`           | Authorization rules                              |
| `app/Http/Requests`      | Input validation                                 |
| `app/Http/Resources`     | Response shaping                                 |
| `app/Console/Commands`   | Operational Artisan commands                     |

## Decision records

Architecture decisions are captured in [`adr/`](adr). Start with
[ADR 0001](adr/0001-record-architecture-decisions.md).
