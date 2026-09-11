# Vo-App Implementation Status

## Current phase
Transactional number marketplace + premium mobile shell.

## Release philosophy
Vo-App is being built as a substantial first release. We are not rushing to market or reducing the product to a small MVP. Release happens only after the planned platform, UX, security, reliability and operational requirements are implemented and validated.

## Repository structure
- `backend/` — Laravel API and platform services
- `mobile/` — Flutter Android/iOS application
- `docs/` — product, architecture, design and implementation source of truth

## Completed in the current engineering pass
- Laravel application shell is present.
- Flutter Android/iOS application shell is present.
- Versioned API contract is documented and registered in Laravel.
- PostgreSQL database/accounting model is documented.
- Core marketplace schema models countries, services, providers, phone-number inventory, offers, orders and persistent number assignments.
- Initial marketplace country/service catalog seeder is present without inventing provider inventory or production prices.
- Read-only marketplace catalog endpoints are available through the versioned API.
- Authenticated number purchase endpoint is registered under `/api/v1/orders/numbers`.
- Number purchase reserves inventory, validates the server-side offer/provider/capability, debits the wallet atomically and creates a pending persistent assignment before dispatching provisioning.
- External provider provisioning is now dispatched as a durable queue job outside the purchase database transaction.
- Provider success finalizes the phone assignment and order under row locks.
- Non-retryable provider failures and exhausted retries release the reservation, fail the assignment/order and issue an idempotent wallet reversal.
- Monthly, quarterly and annual terms are represented as persistent customer assignments with explicit lifecycle dates.
- Purchase requests require a UUID `Idempotency-Key` and repeat requests return the original order rather than charging twice.
- Wallet account aggregate has been added so financial locking does not depend on summing ledger rows.
- Wallet credit/debit operations are idempotent and transaction-scoped.
- Ledger entries are associated with a wallet account and retain an auditable post-entry balance.
- Active number assignment uniqueness is enforced for PostgreSQL.
- Phone-number inventory is modeled independently from historical customer assignments.
- Phone inventory now supports short-lived reservation state for provisioning workflows.
- Provider-neutral number infrastructure contract is defined.
- Provider driver registry is explicit, with configuration separated from credentials and a controlled inventory adapter for the current internal inventory path.
- Provider failures have a dedicated domain exception carrying retryability and provider error codes.
- Number renewal now creates a separately auditable renewal order, charges the wallet idempotently, and dispatches provider renewal asynchronously.
- Renewal provider failures reverse the renewal charge without extending the assignment.
- Non-auto-renewing expired assignments now have a server-side expiry command scheduled hourly, releasing their inventory while retaining historical assignment records.
- Payment-intent persistence is now established with provider-neutral contracts, webhook event storage, idempotency, expiry metadata and a provider manager boundary.
- Payment provider calls are explicitly kept outside database transactions so external payment APIs cannot hold financial locks open.
- SMS and provider webhook persistence models/migrations are defined.
- Sanctum-based authentication endpoints and versioned API routing are established in code.
- Flutter has moved from the generated counter app to a premium Vo-App shell with dashboard, wallet, numbers, messages and marketplace-oriented navigation.
- Unit coverage exercises successful annual purchase, wallet debit, persistent assignment, purchase idempotency and provider-driver resolution.

## Next engineering sequence
1. Get CI fully green and keep dependency locking deterministic.
2. Harden renewal concurrency and add dedicated lifecycle tests for renewal, expiry, retries and wallet reversals.
3. Add a real payment provider adapter and signed webhook reconciliation; never commit provider secrets.
4. Add production number provider adapters behind the provider registry, including authenticated API calls, webhook handling, health checks and failover boundaries.
5. Implement SMS webhook verification, asynchronous processing, deduplication and realtime delivery.
6. Build the complete Flutter authentication flow and API client/session layer.
7. Replace placeholder mobile sections with production marketplace, active-number, SMS, wallet, orders and settings experiences.
8. Add push notifications, deep links, secure device/session management and offline/error states.
9. Build operations/admin tooling, audit trails and provider health controls.
10. Complete integration, security, load and Android/iOS release testing.

## Rule
Do not mark a feature complete because its happy path works. Completion requires domain logic, validation, authorization, failure states, observability, tests and documentation where applicable.
