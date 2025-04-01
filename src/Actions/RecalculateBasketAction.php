<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class RecalculateBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketContext $context,
        protected RecalculateBasketPipeline $recalculateBasketPipeline,
    )
    {

    }

    public function handle(): void
    {
        $basket = $this->context->current();
        $this->recalculateBasketPipeline->handle($basket);
        $this->context->save($basket);
    }
}