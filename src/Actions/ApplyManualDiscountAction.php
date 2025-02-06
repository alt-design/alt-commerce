<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\ManualDiscountItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;

class ApplyManualDiscountAction
{
    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketAction $recalculateBasketAction
    ) {

    }

    public function handle(int $amount, string $description = 'Manual discount'): void
    {

        $discountItem = new ManualDiscountItem(
            name: $description,
            amount: $amount
        );

        $basket = $this->basketRepository->get();

        $basket->discountItems[] = $discountItem;

        $this->recalculateBasketAction->handle();

        $this->basketRepository->save($basket);
    }
}