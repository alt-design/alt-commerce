<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Exceptions\BasketException;

class UpdateLineItemQuantityAction
{
    public function __construct(
        protected BasketContext $context
    )
    {

    }

    /**
     * Update quantity for one specific line item by its id. Unlike
     * UpdateBasketQuantityAction (which matches the first line for a product),
     * this is unambiguous when the same product exists as multiple lines with
     * different options.
     */
    public function handle(string $lineItemId, int $quantity): void
    {
        $basket = $this->context->current();

        foreach ($basket->lineItems as $lineItem) {
            if ($lineItem->id === $lineItemId) {
                $lineItem->quantity = $quantity;

                return;
            }
        }

        throw new BasketException("Basket does not contain line item with id $lineItemId");
    }
}
