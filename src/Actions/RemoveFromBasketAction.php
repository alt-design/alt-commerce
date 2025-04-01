<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
class RemoveFromBasketAction
{

    public function __construct(
        protected BasketContext $context
    )
    {

    }

    public function handle(string ...$productIds): void
    {
        $basket = $this->context->current();
        foreach ($productIds as $productId) {
            foreach ($basket->lineItems as $key => $item) {
                if ($item->productId === $productId ) {
                    unset($basket->lineItems[$key]);
                }
            }

            foreach ($basket->billingItems as $key => $item) {
                if ($item->productId === $productId) {
                    unset($basket->billingItems[$key]);
                }
            }
        }
    }
}