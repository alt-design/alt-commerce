# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows [Semantic Versioning](https://semver.org/). Before 1.0, a minor version can include breaking changes.

## [Unreleased]

### Fixed

- The readme's payment example, which called a method that is not static, and the coupon contracts missing from its table.

## [0.2.1] - 2026-11-25

### Added

- MIT licence.
- Package description, installation notes, a payments guide and this changelog.

## [0.2.0] - 2026-11-24

### Added

- `OrderAlreadyPlacedException`, thrown by `CreateOrderAction` and `AttemptPaymentAction` for an order that is no longer an unpaid draft. Before this, the order was rebuilt or charged again.
- `PaymentOutcomeUnknownException`, thrown when it is not known whether a payment was taken. The order stays blocked until the payment is checked with the gateway.

### Changed

- `AttemptPaymentAction` saves a pending transaction before it calls the gateway.
- Stripe checks the payment again when a capture gets no clear answer.

### Upgrading

- Catch both exceptions in your checkout and do not offer a retry. Declined and rejected payments can be retried as before.
- Products must implement `purchasable()`, added in 0.1.3-alpha.

## [0.1.0-alpha] to [0.1.3-alpha] - 2026-08-07 to 2026-09-17

### Added

- Stock management, with untracked, tracked and backorder policies.
- `purchasable()` on products.

### Changed

- The Stripe integration checks the currency and amount of a payment against the order before it captures it.
