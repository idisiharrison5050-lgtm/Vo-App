# Vo-App Product Requirements Document

## Vision
Vo-App is a premium global virtual-number platform for legitimate communications and verification workflows. It combines a polished Flutter mobile experience with a secure, scalable Laravel platform.

## Platforms
- Android
- iOS
- Laravel web/API platform
- Provider webhooks and background workers

## MVP goals
1. Secure account creation and authentication.
2. Browse virtual numbers by country and service.
3. Support temporary numbers and persistent rentals.
4. Support monthly and annual number assignments.
5. Fund and manage an in-app wallet.
6. Purchase, renew, release, and view assigned numbers.
7. Receive inbound SMS and display messages in near real time.
8. Maintain complete order and wallet transaction history.
9. Provide reliable provider abstraction and webhook processing.
10. Deliver a premium, production-minded mobile UX.

## Number lifecycle
Numbers are inventory assets that may be available, reserved, assigned, suspended, expiring, expired, or released. A persistent number belongs to an explicit assignment/rental record rather than being deleted when a short session ends.

## Core user journeys
- Onboarding -> registration -> verification -> home.
- Wallet -> deposit -> confirmed balance -> purchase.
- Marketplace -> country -> service -> plan duration -> number -> confirmation.
- Active number -> SMS inbox -> live incoming message -> copy code.
- Active number -> renewal -> payment -> extended assignment.
- History -> order details / transaction details.
- Account -> security, devices, notifications, preferences.

## MVP screens
- Splash / launch
- Onboarding
- Sign in / Sign up / account recovery
- Home
- Number marketplace
- Country and service selection
- Number details / purchase confirmation
- Active numbers
- SMS inbox and message detail
- Wallet and funding
- Orders
- Transactions
- Notifications
- Profile and settings
- Security / sessions

## Commercial foundation
The wallet uses a transaction ledger with immutable references and idempotency. Pricing, provider cost, customer price, taxes/fees, refunds, renewals and adjustments are represented explicitly. No security-sensitive or money-moving operation relies solely on client state.

## Provider architecture
Provider integrations must implement a common contract for inventory, reservations, assignments, releases, SMS retrieval, renewals and webhook handling. Provider credentials remain server-side. The platform must be able to add providers and implement failover without rewriting customer-facing domain logic.

## Trust and safety
Vo-App is intended for legitimate communications and verification use. The product must include abuse controls, rate limits, account controls, provider terms compliance, auditability and mechanisms to prevent mass automated abuse or circumvention of third-party anti-abuse systems.

## Non-functional requirements
- Secure by default.
- Mobile-first and accessible.
- Fast perceived performance.
- Idempotent financial and provisioning operations.
- Observable background jobs and provider failures.
- Automated tests around critical domain behavior.
- PostgreSQL-ready production database.
- Redis-ready queues/cache/realtime infrastructure.
- Android and iOS feature parity.

## Future roadmap
Business accounts, teams, sub-users, dedicated numbers, geographic numbers, advanced number search, call capability, voicemail, number porting, analytics, subscriptions, invoices, provider routing intelligence, admin operations center and enterprise controls.
