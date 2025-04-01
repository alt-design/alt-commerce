<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class EmptyBasketAction
{

    public function __construct(
        protected BasketContext $context,
    )
    {

    }

    public function handle(): void
    {
        $this->context->clear();
    }

}