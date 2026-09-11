# Vo-App Architecture

## Stack
- Laravel 12+ / PHP 8.3+
- PostgreSQL for production persistence
- Redis for cache, queues and realtime support
- Laravel Reverb for realtime events where appropriate
- Laravel Horizon for queue visibility and workers
- Flutter for Android and iOS
- Provider adapters behind a domain-level contract

## Boundaries
### Flutter
Presentation, navigation, local state, secure token storage, API client, realtime client, permissions, polished UX and device capabilities.

### Laravel API
Authentication, authorization, domain rules, pricing, wallet ledger, number lifecycle, orders, SMS, notifications, provider orchestration and auditability.

### Provider layer
External virtual-number/SMS providers are isolated behind adapters. Provider-specific IDs and payloads do not leak into core domain logic.

### Async processing
Webhooks, SMS synchronization, provisioning, renewal, expiry, notifications and reconciliation should be queue-backed and observable.

## Domain model
User -> Wallet -> WalletTransaction
User -> NumberAssignment -> PhoneNumber
PhoneNumber -> ProviderNumber
NumberAssignment -> NumberOrder
PhoneNumber -> SmsMessage
Country -> Service -> PhoneNumber
Provider -> ProviderNumber / ProviderTransaction

## Financial rules
- Store monetary values as integer minor units where practical.
- Every balance-affecting operation creates a durable ledger entry.
- Use idempotency keys for deposits, purchases, refunds and provider callbacks.
- Never trust client-supplied prices or balances.
- Reconcile provider charges against internal records.

## Security
Authentication tokens are revocable. Authorization is policy-based. Sensitive operations are rate-limited and audited. Provider credentials and payment secrets are never shipped to Flutter.

## Realtime
Server events can notify clients of incoming SMS, order state changes, wallet events and number status changes. The UI must degrade gracefully to polling when realtime connectivity is unavailable.

## Deployment target
Container-friendly Laravel application with managed PostgreSQL and Redis, HTTPS, queue workers, scheduler, object storage where needed, centralized logs and health checks.
