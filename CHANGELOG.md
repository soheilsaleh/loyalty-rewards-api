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

[Unreleased]: https://github.com/OWNER/loyalty-rewards-api/commits/main
