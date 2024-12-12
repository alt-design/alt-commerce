<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Contracts\BasketRepository;

class RemoveFromBasketAction
{

    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    public function handle(string $productId): void
    {
        $basket = $this->basketRepository->get();
        foreach ($basket->lineItems as $key => $item) {
            if ($item->product->id() === $productId ) {
                unset($basket->lineItems[$key]);
            }
        }

        $this->basketRepository->save($basket);
        $this->recalculateBasketAction->handle();
    }
}