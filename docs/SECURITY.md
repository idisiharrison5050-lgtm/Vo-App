# Vo-App Security Baseline

Vo-App is a communications platform and must assume that both account abuse and provider-facing abuse will occur at scale.

## Account security
- Passwords are hashed using Laravel's supported password hashing.
- Sanctum tokens are revocable and scoped to the authenticated device/session model.
- Login, registration, recovery and sensitive commands are rate limited.
- Sensitive account actions can require recent authentication and step-up verification.
- Session/device management is visible to customers.

## Authorization
Every customer resource is authorized by ownership and domain policy. Numeric IDs, assignment IDs and order references are never treated as authorization by themselves.

## Financial safety
- Never trust client prices, balances, fees or wallet state.
- Use integer minor units for money.
- All financial commands are idempotent.
- Wallet debits require an atomic server-side balance check.
- Reversals create new ledger entries; historical entries are not mutated.
- Payment webhooks are authenticated, deduplicated and reconciled.

## Provider security
Provider credentials exist only on trusted backend infrastructure. They are never shipped to Flutter, logged, returned in API responses or embedded in mobile configuration.

Inbound webhooks require signature/authentication verification where supported, event deduplication, schema validation and replay-safe processing.

## Number and SMS abuse controls
Vo-App supports legitimate communications and verification workflows. The platform must not provide tooling intended to mass-create accounts, defeat third-party anti-abuse controls, evade provider restrictions or automate fraudulent verification activity.

Controls include:
- per-account and per-device rate limits;
- velocity limits on number searches, reservations and purchases;
- spend limits and risk thresholds;
- provider/country/service restrictions;
- suspicious-activity review signals;
- audit trails for sensitive operations;
- account suspension and provider-level blocking;
- retention policies for sensitive message data.

## Data protection
SMS contents and account data are sensitive. Access is limited by authorization, administrative roles and audit logging. Secrets and personally sensitive values must not appear in application logs.

## Operational security
Production deployment must use TLS, secret management, least-privilege service credentials, database backups, queue isolation, health checks, alerting and tested restoration procedures.

## Release gate
Security is part of feature completion. A feature cannot be considered production-ready until validation, authorization, rate limiting, failure handling, logging/observability and abuse controls have been considered and tested.
