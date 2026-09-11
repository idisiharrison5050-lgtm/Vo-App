# Vo-App

## The Product
Vo-App is a premium global virtual-number platform for legitimate communications and verification workflows. Customers can discover, purchase, rent, renew and manage virtual phone numbers, receive SMS in near real time, and manage payments through a secure wallet.

Vo-App is being built for **Android and iOS with Flutter**, backed by a **Laravel API/platform** and production-grade infrastructure.

## Product Standard

Vo-App is **not being built as a small MVP or rushed first release**.

The first public release is intended to be a substantial, polished product. We will finish the core platform, advanced customer experience, provider infrastructure, financial system, security, operations tooling, reliability and testing before release. There is no artificial deadline to ship an incomplete product.

### Build principle
> **Build first. Release later. Think big. Quality over speed.**

We are optimizing for a product that can grow into a serious global communications platform, not a disposable prototype.

## Initial Platform Scope

- Premium Android and iOS application
- Global country/service number marketplace
- Temporary and long-term number products
- Monthly, yearly and other rental durations
- Persistent number assignments and renewals
- SMS inbox with near-real-time delivery
- Wallet, deposits, refunds and immutable transaction ledger
- Orders, receipts and transaction history
- Notifications and account/security controls
- Multi-provider inventory and SMS architecture
- Provider webhooks, reconciliation and failover
- Abuse prevention, rate limits and auditability
- Admin/operations capabilities
- Production observability, automated testing and CI/CD
- Architecture for business accounts, teams, dedicated numbers, calling, voicemail, porting and enterprise capabilities

## Repository Structure

```text
backend/   Laravel API and platform
mobile/    Flutter Android/iOS application
docs/      Product, architecture, design and engineering source of truth
infra/     Deployment and infrastructure configuration
.github/   CI/CD and engineering automation
```

## Engineering Rule
A feature is not considered finished merely because it works on the happy path. It must have the appropriate domain model, API contract, UI states, validation, security controls, failure handling, tests, observability and documentation.

## Status
**Building — pre-release.**

Release will happen only after the planned platform is built and the product passes comprehensive functional, security, reliability and UX review.
