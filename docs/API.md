# Vo-App API Contract

## Principles
- Versioned under `/api/v1`.
- JSON only for mobile API responses.
- Authentication uses Laravel Sanctum bearer tokens.
- Money is represented in integer minor units; the client never supplies authoritative prices or balances.
- Every money-moving or inventory-changing command accepts an idempotency key.
- Resource identifiers are opaque and stable; provider identifiers remain server-side implementation details.
- Authorization is enforced server-side for every user-owned resource.
- Errors use a consistent envelope with a machine-readable `code`, human-readable `message`, and optional field errors.

## Authentication

### POST `/api/v1/auth/register`
Creates a customer account after validation and abuse/risk checks.

### POST `/api/v1/auth/login`
Authenticates a customer and returns an access token plus the current account summary.

### POST `/api/v1/auth/logout`
Revokes the current access token.

### GET `/api/v1/me`
Returns the authenticated account profile and security state.

## Marketplace

### GET `/api/v1/catalog/countries`
Returns active countries available for number inventory.

### GET `/api/v1/catalog/services`
Returns supported services/capabilities.

### GET `/api/v1/numbers`
Searches available inventory using server-side filters such as country, capability, term type and price band.

### GET `/api/v1/numbers/{id}`
Returns a number offer and its current availability. Availability is authoritative at purchase time.

## Number lifecycle

### POST `/api/v1/orders/numbers`
Creates a number order. The server calculates price, reserves inventory, validates wallet funds and provisions through the provider layer.

### GET `/api/v1/numbers/active`
Returns the customer's active number assignments, including start/end dates, renewal state and capabilities.

### POST `/api/v1/numbers/{assignment}/renew`
Renews an eligible assignment using the current server-side price.

### POST `/api/v1/numbers/{assignment}/release`
Releases a number assignment when the product rules permit it.

Annual and monthly numbers are represented as persistent assignments with explicit lifecycle dates. The underlying phone-number inventory record is retained independently from the customer relationship.

## SMS

### GET `/api/v1/numbers/{assignment}/messages`
Returns paginated inbound SMS messages for an authorized assignment.

### GET `/api/v1/messages/{id}`
Returns a single authorized SMS message.

Provider webhooks are authenticated, deduplicated and processed asynchronously. Realtime delivery is emitted only after the event is accepted by the domain pipeline.

## Wallet

### GET `/api/v1/wallet`
Returns the current wallet account and available balance.

### GET `/api/v1/wallet/transactions`
Returns immutable ledger entries with pagination and filters.

### POST `/api/v1/wallet/funding-intents`
Creates a funding intent with a payment provider. The client never directly changes its wallet balance.

## Orders

### GET `/api/v1/orders`
Returns the authenticated customer's orders.

### GET `/api/v1/orders/{reference}`
Returns an order and its lifecycle state.

## Response envelope

Success:
```json
{
  "success": true,
  "message": "OK",
  "data": {}
}
```

Error:
```json
{
  "success": false,
  "message": "The requested number is no longer available.",
  "code": "NUMBER_UNAVAILABLE",
  "errors": {}
}
```

## Concurrency requirements
Number reservation, wallet debits, assignment creation and renewals must be transactionally coordinated. Duplicate requests must return the original command result rather than performing a second side effect.
