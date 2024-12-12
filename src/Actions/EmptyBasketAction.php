<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Contracts\BasketRepository;

class EmptyBasketAction
{

    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    public function handle(): void
    {
        $basket = $this->basketRepository->get();
        $basket->lineItems = [];
        $this->basketRepository->save($basket);

        $this->recalculateBasketAction->handle();
    }

}