# Alt Commerce

![Tests](https://github.com/alt-design/alt-commerce/actions/workflows/test.yml/badge.svg)

> The ecommerce core behind Alt Commerce: baskets, tax, coupons, orders, stock and payments.

This package holds the rules and is not tied to a framework. You supply the storage by implementing its contracts. If you use Statamic, install [alt-design/alt-commerce-statamic](https://github.com/alt-design/alt-commerce-statamic) instead, which does that for you and installs this package.

## Requirements

- PHP 8.3 or later, with the `curl`, `json` and `mbstring` extensions
- A Stripe account to take payments

## How to Install

``` bash
composer require alt-design/alt-commerce
```

## Contracts to implement

Each one is in `AltDesign\AltCommerce\Contracts`.

| Contract | What it is for |
| --- | --- |
| `Settings` | Trading name, default country and currency, supported currencies and tax |
| `Product`, `ProductRepository` | What you sell, its prices, tax rules and stock policy |
| `Customer`, `CustomerRepository` | Who is buying |
| `OrderRepository`, `OrderFactory` | Saving and loading orders |
| `StockRepository` | Counted stock and its history |
| `CouponRepository` | Discount codes |
| `BasketDriver`, `BasketDriverFactory` | Where a basket is kept between requests |
| `VisitorLocator` | The visitor's country and currency. Return `null` to use your settings |
| `Resolver` | Builds classes, usually from your container |

## Payments

Stripe is the supported gateway. It uses a payment intent with manual capture, so the card is authorised first and charged once the order exists.

1. Create the payment with `PaymentManager::currency('GBP')->authToken()` and confirm it in the browser with Stripe.js.
2. Create the order with `createOrder()` on the basket context.
3. Charge it with `attemptPayment()`, passing the payment intent's ID.

The amount and currency of the payment are checked against the order before any money is taken.

Your checkout needs to handle two exceptions:

| Exception | What happened | What to do |
| --- | --- | --- |
| `PaymentOutcomeUnknownException` | It is not known whether the payment was taken | Do not offer a retry. Check the payment in Stripe |
| `OrderAlreadyPlacedException` | The order has been placed, paid or is being checked | Do not charge again |

After any other exception, that attempt took no payment and the customer can try again.

## External services

Nothing is sent to Alt Design. Payment requests go to Stripe with the basket's total and currency. If you pass them, a description and the customer's email address, name, phone number and delivery address go too.

## Testing

``` bash
vendor/bin/phpunit tests
vendor/bin/phpstan analyse --memory-limit 512m
```

## Changelog and licence

Changes are listed in [CHANGELOG.md](CHANGELOG.md). Alt Commerce is open source under the [MIT licence](LICENSE).

For help, email support@alt-design.net or [open an issue](https://github.com/alt-design/alt-commerce/issues).
