<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class RecalculateBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected RecalculateBasketPipeline $recalculateBasketPipeline,
    )
    {

    }

    public function handle(): void
    {
        $this->recalculateBasketPipeline->handle();
    }
}