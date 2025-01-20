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
        $this->basketRepository->delete();
        $this->recalculateBasketAction->handle();
    }

}