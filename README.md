# Loyalty & Rewards API

[![CI](https://github.com/soheilsaleh/loyalty-rewards-api/actions/workflows/ci.yml/badge.svg)](https://github.com/soheilsaleh/loyalty-rewards-api/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/soheilsaleh/loyalty-rewards-api)](https://github.com/soheilsaleh/loyalty-rewards-api/releases)
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

> **Status:** **v1.0.0** — all eight phases are delivered: the API, the Docker
> stack, tests, CI, benchmarks and documentation are complete. See the
> [Roadmap](#roadmap) and [`CHANGELOG.md`](CHANGELOG.md).

## Features

Everything below ships in v1.0 (see the roadmap for the delivery order):

- Authentication and roles (`admin`, `staff`, `customer`) with Sanctum tokens
- Append-only **points ledger** — the balance is always the sum of the ledger
- Automatic **tier** upgrades based on lifetime points
- Purchase registration that awards points from a deterministic formula
- **Concurrency-safe reward redemption** with idempotency keys
- Campaigns with points multipliers and a weighted **spin wheel**
- **FIFO points expiry** driven by a scheduled job
- Inbound **HMAC-signed webhooks**
- Cached **admin reporting summary** and an **append-only audit trail**
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
| API docs           | OpenAPI 3.1 spec + Postman collection   |
| Local stack        | Docker Compose (nginx, php-fpm, mysql, redis, mailpit, queue, scheduler) |
| CI                 | GitHub Actions + gitleaks               |

## Architecture

The codebase follows a thin-controller design: controllers validate input, call
a service, and return an API Resource. Domain rules live in `app/Services`, and
every operation that touches more than one table runs inside a database
transaction.

```
app/
  Enums/            Role, TransactionType, RedemptionStatus, AuditAction, ...
  Models/
  Http/
    Controllers/Api/V1/
    Requests/        FormRequest validation
    Resources/       API responses
    Middleware/       EnsureRole, VerifyWebhookSignature
  Services/          PointsService, TierService, RedemptionService, CampaignService, SpinService, ReportService, AuditService
  Jobs/              SendExpiryReminder
  Events/ Listeners/  PurchaseRegistered -> SendPurchaseReceipt
  Policies/
  Console/Commands/  loyalty:expire-points, loyalty:notify-expiring-points, loyalty:reconcile, loyalty:seed-demo
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

For benchmarking there is a separate generator that can create a large dataset
without touching the demo seed:

```bash
docker compose exec -T app php artisan loyalty:seed-demo --members=5000
```

## API documentation

The full contract lives in [`docs/openapi.yaml`](docs/openapi.yaml) (OpenAPI 3.1)
and can be imported into Postman, Insomnia, Swagger UI or any OpenAPI tooling. A
ready-to-run [Postman collection](docs/postman_collection.json) is included too:
set `baseUrl`, run **Auth / Login** (it stores the bearer token), and go.

A test (`tests/Feature/Docs/OpenApiTest.php`) fails the build if the spec and the
router drift apart, so the documentation cannot silently go stale.

### Example requests

```bash
BASE=http://localhost:8000/api/v1

# Log in as the seeded admin and capture the token
TOKEN=$(curl -s -X POST "$BASE/auth/login" \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@loyalty.test","password":"password"}' | jq -r .token)

# Register a purchase (staff/admin) — awards points
curl -s -X POST "$BASE/purchases" \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"account_id":2,"amount_cents":4999,"external_id":"order-1001"}'

# The current member's account and ledger
curl -s "$BASE/me/account"     -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/me/transactions" -H "Authorization: Bearer $TOKEN"

# Redeem a reward (requires a unique idempotency key)
curl -s -X POST "$BASE/rewards/1/redeem" \
  -H "Authorization: Bearer $TOKEN" -H "Idempotency-Key: $(uuidgen)"

# Admin reporting summary
curl -s "$BASE/reports/summary" -H "Authorization: Bearer $TOKEN"
```

### Signed webhooks

```bash
BODY='{"account_id":2,"amount_cents":2599,"external_id":"pos-9001"}'
TS=$(date +%s)
SIG=$(printf '%s.%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$LOYALTY_WEBHOOK_SECRET" -hex | awk '{print $2}')

curl -s -X POST "$BASE/webhooks/purchase" \
  -H 'Content-Type: application/json' \
  -H "X-Timestamp: $TS" -H "X-Signature: $SIG" \
  -d "$BODY"
```

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

The results below were measured locally on a seeded dataset of **5,001 accounts
and 15,002 ledger rows** (step 3 below), 1 vCPU / 2 GB container. Reproduce them
with:

```bash
docker compose exec -T app php artisan loyalty:seed-demo --members=5000
```

### Query indexes

The expiry scan and the reporting aggregates were the two hot queries, so
`point_transactions` gained indexes on `type` and `(points_remaining,
expires_at)`, and `purchases` on `occurred_at` (migration
`..._add_performance_indexes`). The planner now uses them instead of scanning:

```text
EXPLAIN SELECT id FROM point_transactions
  WHERE points_remaining > 0 AND expires_at <= NOW();
-- key: point_transactions_expires_at_index  | type: range | rows: 1

EXPLAIN SELECT SUM(points) FROM point_transactions WHERE type = 'redeem';
-- key: point_transactions_type_index         | type: ref
```

### Cached reporting summary

`GET /api/v1/reports/summary` aggregates the whole ledger, so the result is
cached in Redis (`loyalty.reports.cache_ttl`, default 60s):

| Path                     | Time     |
| ------------------------ | -------- |
| Cache miss (build)       | ~86 ms   |
| Cache hit (read)         | ~0.5 ms  |

That is roughly **170× faster** on a warm cache, and it keeps the aggregate off
the database during dashboard traffic spikes.

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
- [x] **Phase 6** — Reports, caching, performance tuning, audit trail
- [x] **Phase 7** — API documentation (OpenAPI + Postman) and ADRs
- [x] **Phase 8** — v1.0.0 release

### Known limitations

- No frontend, no real payment processing and no real SMS sending.
- Single-tenant only in v1.0 (multi-tenancy is a post-1.0 exploration).

## License

[MIT](LICENSE)
