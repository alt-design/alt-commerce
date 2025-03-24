<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class RecalculateBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketPipeline $recalculateBasketPipeline,
    )
    {

    }

    public function handle(): void
    {
        $basket = $this->basketRepository->get();
        $this->recalculateBasketPipeline->handle($basket);
        $this->basketRepository->save($basket);
    }
}