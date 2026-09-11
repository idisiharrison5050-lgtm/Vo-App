# Vo-App Design System

Vo-App must feel like a premium communications/fintech product. Functionality is not enough; every surface should communicate trust, clarity and quality.

## Principles
1. Calm over clutter.
2. One primary action per screen.
3. Numbers, prices and statuses are highly legible.
4. Motion communicates state rather than decoration.
5. Never use generic admin-dashboard styling as the mobile product.
6. Android and iOS share the same visual language while respecting platform conventions.

## Product surfaces
- Home: status, wallet, active numbers and quick actions.
- Marketplace: country/service discovery, availability and duration plans.
- Number detail: number identity, capabilities, rental term, renewal date and purchase CTA.
- SMS: conversation-like inbox with clear verification code treatment.
- Wallet: balance, funding and ledger.
- Account: identity, security, devices, preferences.

## Visual system
Use a consistent spacing scale, typography hierarchy, semantic colors, elevation and corner-radius tokens. Prefer restrained surfaces, strong contrast and generous whitespace. Avoid excessive gradients, glassmorphism and decorative effects that reduce clarity.

## States
Every major feature must design loading, empty, success, partial, offline, expired, unavailable and recoverable-error states.

## Accessibility
Respect dynamic text sizing, semantic labels, sufficient contrast, touch target sizing, reduced motion preferences and screen-reader navigation.

## Cross-platform
Build with Flutter's adaptive capabilities. Navigation, permissions, system UI, keyboard behavior, haptics and dialogs should feel native on both Android and iOS without duplicating business logic.
