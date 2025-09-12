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

    public function handle(string ...$lineItemIds): void
    {
        $basket = $this->context->current();
        foreach ($lineItemIds as $lineItemId) {
            foreach ($basket->lineItems as $key => $item) {
                if ($item->id === $lineItemId) {
                    unset($basket->lineItems[$key]);
                }
            }

            foreach ($basket->billingItems as $key => $item) {
                if ($item->id === $lineItemId) {
                    unset($basket->billingItems[$key]);
                }
            }
        }
    }
}