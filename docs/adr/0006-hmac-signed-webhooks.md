# 6. HMAC-signed inbound webhooks

- Status: Accepted
- Date: 2026-10-05

## Context

External point-of-sale systems push purchases to `POST /webhooks/purchase`.
Unlike our own clients, these callers are not users and cannot hold a Sanctum
token. The endpoint must still reject forged or replayed requests.

## Decision

Webhooks are authenticated with a shared secret using **HMAC-SHA256**. The
provider signs `timestamp + '.' + raw body` and sends the digest in
`X-Signature` plus the timestamp in `X-Timestamp`. Middleware verifies the
signature with `hash_equals` (constant time) and rejects timestamps outside a
configurable tolerance to mitigate replay. If no secret is configured the
endpoint returns `503` rather than accepting unsigned traffic. The payload's
`external_id` makes processing idempotent.

## Consequences

- No bearer token is needed for machine-to-machine calls.
- Signature comparison is constant time and replay is bounded by the tolerance.
- The secret and tolerance live in configuration/env, never in code, and are
  never logged.
