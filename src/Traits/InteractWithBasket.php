<?php

namespace AltDesign\AltCommerce\Traits;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;

trait InteractWithBasket
{
    protected function find(Basket $basket, string $productId): ?LineItem
    {
        foreach ($basket->lineItems as $item) {
            if ($item->productId === $productId) {
                return $item;
            }
        }
        return null;
    }
}