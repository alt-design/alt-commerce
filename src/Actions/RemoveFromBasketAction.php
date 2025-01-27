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

    public function handle(string ...$productIds): void
    {
        $basket = $this->basketRepository->get();
        foreach ($productIds as $productId) {
            foreach ($basket->lineItems as $key => $item) {
                if ($item->productId === $productId ) {
                    unset($basket->lineItems[$key]);
                }
            }

            foreach ($basket->billingItems as $key => $item) {
                if ($item->productId === $productId) {
                    unset($basket->billingItems[$key]);
                }
            }
        }
        $this->basketRepository->save($basket);
        $this->recalculateBasketAction->handle();
    }
}