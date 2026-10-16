# 1. Record architecture decisions

- Status: Accepted
- Date: 2026-01-01

## Context

This project makes several non-obvious design choices (an append-only ledger,
money as integer cents, idempotent redemptions, FIFO expiry). Reviewers and
future contributors need to understand *why* each choice was made, not just
*what* was implemented.

## Decision

We will keep a lightweight Architecture Decision Record (ADR) for every
significant decision. Each record is a short Markdown file in `docs/adr/`,
numbered sequentially, with the sections: Context, Decision, Consequences.

## Consequences

- The reasoning behind the system is documented alongside the code.
- Reviewers can follow the project's evolution through `git log` and `docs/adr/`.
- A small amount of discipline is required to keep records up to date.
