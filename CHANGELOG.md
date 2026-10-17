# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Project skeleton based on Laravel 12 (PHP 8.3).
- Docker Compose stack: nginx, php-fpm, MySQL 8, Redis 7, Mailpit, queue and
  scheduler workers, with health checks.
- Multi-stage Dockerfile with a non-root production image.
- Makefile with `up`, `down`, `test`, `lint`, `stan`, `fresh`, `ci`.
- GitHub Actions pipeline running Pint, Larastan and Pest against real MySQL and
  Redis, plus a gitleaks secret scan.
- Base documentation: README, `docs/ARCHITECTURE.md` and ADR 0001.
- Token-based authentication with Laravel Sanctum: register, login, logout and
  profile endpoints under `/api/v1/auth`.
- Role-based access control with an admin/staff/customer `Role` enum, a `role`
  middleware alias and demo seed accounts.
- Login rate limiting and full feature test coverage for auth and role access.
- Member accounts with a points balance, lifetime points and automatic tier
  upgrades (Bronze, Silver, Gold, Platinum).
- An append-only points ledger: every balance change is an immutable row and the
  cached balance is kept equal to the sum of the ledger.
- Purchase registration with a deterministic earning rate, and refunds that
  reverse the points a purchase awarded.
- Admin manual adjustments, plus the `/me/account` and `/me/transactions`
  endpoints.
- A reward catalog with active/inactive rewards and optional stock, managed by
  admins through create, update and delete endpoints.
- Concurrency-safe redemption: the account row is locked, points are consumed
  FIFO from the oldest batches, and an `Idempotency-Key` header makes retries
  return the original redemption instead of spending points twice.
- Time-boxed campaigns with a points multiplier; purchases earn at the highest
  multiplier among the campaigns that were running at the time.
- A weighted **spin wheel** where a member spins each campaign once; prizes
  award bonus points and optional stock is tracked.
- Scheduled jobs that expire points FIFO and email members a reminder before
  their points expire (deduplicated per batch).
- An inbound `POST /webhooks/purchase` endpoint signed with HMAC-SHA256 and a
  timestamp tolerance; the `external_id` makes it idempotent.
- Queued purchase receipts and a `loyalty:reconcile` command that verifies the
  cached balance against the ledger.

[Unreleased]: https://github.com/soheilsaleh/loyalty-rewards-api/commits/main
