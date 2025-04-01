<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class UpdateBasketCountryAction
{
    public function __construct(
        protected BasketContext $context,
    )
    {

    }

    public function handle(string $countryCode): void
    {
        $basket = $this->context->current();

        if ($basket->countryCode === $countryCode) {
            return;
        }

        $basket->countryCode = $countryCode;
        $this->context->save($basket);
    }
}