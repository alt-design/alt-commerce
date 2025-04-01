<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;

class UpdateBasketCurrencyAction
{
    public function __construct(
        protected BasketContext $context,
        protected BasketRepository $basketRepository,
        protected ProductRepository $productRepository,
        protected Settings $settings,
    )
    {

    }

    public function handle(string $currency): void
    {
        $basket = $this->context->current();

        $currency = strtoupper($currency);

        if ($basket->currency === $currency) {
            return;
        }

        if (!in_array($currency, $this->settings->supportedCurrencies())) {
            throw new CurrencyNotSupportedException("Currency $currency is not supported");
        }

        // remove line items that do not support new currency
        foreach ($basket->lineItems as $key => $item) {
            $product = $this->productRepository->find($item->productId);
            if ($product && $product->price()->isCurrencySupported($currency)) {
                $basket->lineItems[$key]->subTotal = $product->price()->getAmount($currency, ['quantity' => $item->quantity]);
                continue;
            }

            unset($basket->lineItems[$key]);
        }

        // remove billing items that do not support currency
        foreach ($basket->billingItems as $key => $item) {
            $product = $this->productRepository->find($item->productId);
            if ($product && $product->price()->hasBillingPlan()) {
                $billingPlan = $product->price()->getBillingPlan($currency, ['plan' => $item->id]);
                if ($billingPlan->prices->isCurrencySupported($currency)) {
                    $basket->billingItems[$key]->amount = $billingPlan->prices->getAmount($currency);
                    continue;
                }
            }

            unset($basket->billingItems[$key]);
        }

        $basket->currency = $currency;

    }
}