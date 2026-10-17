# Loyalty & Rewards API

[![CI](https://github.com/soheilsaleh/loyalty-rewards-api/actions/workflows/ci.yml/badge.svg)](https://github.com/soheilsaleh/loyalty-rewards-api/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com/)

A production-minded REST API for a customer loyalty program, built from scratch
with Laravel. It manages members, tiers and an **append-only points ledger**,
computes purchase points, and lets customers redeem rewards safely under
concurrent load.

The project exists to demonstrate the parts of backend engineering that matter
in real systems: database design, transactions and concurrency, queues,
caching, testing, CI, Docker and documentation — with **real, reproducible
numbers** instead of vague claims.

> **Status:** Phase 5 — queued notifications, the scheduled expiry job, inbound
> HMAC webhooks and email are in place. Feature work lands phase by phase (see
> [Roadmap](#roadmap)).

## Features

Planned for v1.0 (see the roadmap for the delivery order):

- Authentication and roles (`admin`, `staff`, `customer`) with Sanctum tokens
- Append-only **points ledger** — the balance is always the sum of the ledger
- Automatic **tier** upgrades based on lifetime points
- Purchase registration that awards points from a deterministic formula
- **Concurrency-safe reward redemption** with idempotency keys
- Campaigns with points multipliers and a weighted **spin wheel**
- **FIFO points expiry** driven by a scheduled job
- Inbound **HMAC-signed webhooks**
- OpenAPI docs, Postman collection, automated tests and CI

## Tech stack

| Concern            | Choice                                  |
| ------------------ | --------------------------------------- |
| Language / runtime | PHP 8.3                                 |
| Framework          | Laravel 12                              |
| Database           | MySQL 8                                 |
| Cache / queue / locks | Redis 7                              |
| API auth           | Laravel Sanctum                         |
| Tests              | Pest                                    |
| Code style         | Laravel Pint                            |
| Static analysis    | Larastan (PHPStan), level 6             |
| API docs           | Scribe / OpenAPI *(planned)*            |
| Local stack        | Docker Compose (nginx, php-fpm, mysql, redis, mailpit, queue, scheduler) |
| CI                 | GitHub Actions + gitleaks               |

## Architecture

The codebase follows a thin-controller design: controllers validate input, call
a service, and return an API Resource. Domain rules live in `app/Services`, and
every operation that touches more than one table runs inside a database
transaction.

```
app/
  Enums/            Role, TransactionType, RedemptionStatus, ...
  Models/
  Http/
    Controllers/Api/V1/
    Requests/        FormRequest validation
    Resources/       API responses
    Middleware/       EnsureRole, VerifyWebhookSignature
  Services/          PointsService, TierService, RedemptionService, CampaignService, SpinService
  Jobs/              SendExpiryReminder
  Events/ Listeners/  PurchaseRegistered -> SendPurchaseReceipt
  Policies/
  Console/Commands/  loyalty:expire-points, loyalty:notify-expiring-points, loyalty:reconcile
```

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for diagrams and
[`docs/adr/`](docs/adr) for the architecture decision records.

## Quick start

Requirements: [Docker](https://docs.docker.com/get-docker/) and `make`.

```bash
cp .env.example .env
make up
make fresh
```

That is enough: the stack builds, waits for MySQL/Redis, runs the migrations and
seeds demo data. The API is served at `http://localhost:8000`, and Mailpit's
inbox at `http://localhost:8025`.

Run the checks locally the same way CI does:

```bash
make ci
```

## Demo data

Demo users are created **only** by the seeder and are intended for local
development. The passwords are non-secret placeholders and must never be reused.

| Role     | Email                  | Password   |
| -------- | ---------------------- | ---------- |
| admin    | admin@loyalty.test     | `password` |
| staff    | staff@loyalty.test     | `password` |
| customer | customer@loyalty.test  | `password` |

Seeded by `DatabaseSeeder` (run automatically by `make fresh`).

## Example requests

*(Added in Phase 7 together with the OpenAPI / Postman collection.)*

## Design decisions

Short version; full reasoning lives in [`docs/adr`](docs/adr).

- **Append-only ledger.** Points never live only in a mutable balance column.
  `point_transactions` is INSERT-only and the account's `points_balance` is a
  cache that must always equal the sum of its ledger rows. A test and the
  `loyalty:reconcile` command enforce this invariant.
- **Money as integer cents.** All amounts are stored as `amount_cents` integers
  to avoid floating-point rounding errors.
- **Idempotency keys.** Redemption requests carry an `Idempotency-Key` so a
  retried request returns the original result instead of charging twice.
- **FIFO expiry.** Each earned batch carries `points_remaining`; redemptions
  consume the oldest points first and a scheduled job expires what is left.
- **HMAC with `hash_equals`.** Inbound webhooks are signed and compared in
  constant time, with a timestamp check to mitigate replay attacks.

## Performance

*(Filled in Phase 6: `EXPLAIN` output and response times before/after indexing,
measured against a seeded dataset of 100k members and 1M ledger rows.)*

## Testing

Tests run against a **real MySQL** instance in CI (not SQLite) because locking
and transaction behaviour differ.

```bash
make test       # run the suite
make coverage   # require at least 80% coverage
```

## Roadmap

- [x] **Phase 0** — Skeleton, Docker Compose stack, Makefile, CI
- [x] **Phase 1** — Auth, roles, rate limiting
- [x] **Phase 2** — Accounts, tiers, ledger, purchases
- [x] **Phase 3** — Rewards and concurrency-safe redemption
- [x] **Phase 4** — Campaigns and the spin wheel
- [x] **Phase 5** — Queues, scheduler, webhooks, mail
- [ ] **Phase 6** — Reports, caching, performance tuning
- [ ] **Phase 7** — API documentation and ADRs
- [ ] **Phase 8** — v1.0.0 release

### Known limitations

- No frontend, no real payment processing and no real SMS sending.
- Single-tenant only in v1.0 (multi-tenancy is a post-1.0 exploration).

## License

[MIT](LICENSE)
