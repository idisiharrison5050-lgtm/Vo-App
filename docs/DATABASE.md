# Vo-App Database Design

Production database: PostgreSQL.

## Identity
`users` remains Laravel's account table. Authentication/session tokens are handled by Sanctum. Customer state belongs to the account, not to phone-number inventory.

## Number inventory
- `countries` — normalized country catalogue.
- `services` — supported communications/verification use cases.
- `providers` — provider registry and health/routing metadata.
- `phone_numbers` — durable inventory assets. A number is not deleted when a customer releases it.
- `number_assignments` — customer ownership/rental relationship, with explicit term, lifecycle and renewal state.

A single phone number can therefore have many historical assignments while only one active assignment may exist at a time. Annual, monthly and other long-term products use the same assignment model rather than creating disposable number records.

## Commerce
`orders` records the commercial command and authoritative server-side price snapshot. Order references are unique and stable. Provider provisioning state must not be inferred from payment state alone.

Future commerce tables include payment intents, payment transactions, refunds, pricing rules, invoices and promotions.

## Wallet accounting
The wallet is an accounting subsystem, not a mutable `balance` field.

- Wallet account: one row per user/currency for locking and current aggregate state.
- Ledger entries: append-only financial events.
- `amount_minor`: integer minor units.
- `direction`: credit/debit.
- `balance_after_minor`: auditable post-entry balance.
- `idempotency_key`: unique command key preventing duplicate financial effects.
- `reference`: externally safe transaction reference.

A debit must never be accepted merely because a client reports sufficient funds. The server locks the wallet account, verifies available funds, creates the immutable debit entry, updates the aggregate balance, and commits atomically.

## SMS
- `sms_messages` stores normalized inbound messages and provider metadata.
- `provider_webhook_events` stores the raw event identity/state for deduplication and replay-safe processing.

Provider callbacks are untrusted input until authenticated and validated. Webhook processing is idempotent.

## Integrity rules
1. Foreign keys protect ownership relationships.
2. Unique constraints protect normalized phone numbers, order references and idempotency keys.
3. Active assignment uniqueness is enforced at the database/domain level, not only in application code.
4. Financial rows are never edited to rewrite history; reversals are new entries.
5. Provider references are not exposed as customer-facing identifiers.
6. Monetary values use integer minor units.
7. Timestamps are stored consistently and rendered in the customer's locale by the client.

## Scaling direction
Indexes will follow real query patterns: marketplace availability, user active assignments, SMS inbox ordering, webhook deduplication, order lookup and wallet history. PostgreSQL partitioning is reserved for demonstrably high-volume append-only tables such as SMS and ledger history rather than added prematurely.
