<?php

namespace AltDesign\AltCommerce\Traits;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;

trait InteractWithBasket
{
    protected function find(Basket $basket, string $productId, array $options = []): LineItem|BillingItem|null
    {
        ksort($options);

        foreach ($basket->lineItems as $item) {
            ksort($item->options);
            if ($item->productId === $productId && $item->options == $options) {
                return $item;
            }
        }

        foreach ($basket->billingItems as $item) {
            if ($item->productId === $productId) {
                return $item;
            }
        }

        return null;
    }
}