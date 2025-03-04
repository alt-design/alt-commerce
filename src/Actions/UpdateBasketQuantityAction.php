<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Exceptions\BasketException;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class UpdateBasketQuantityAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketRepository $basketRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    public function handle(string $productId, int $quantity): void
    {
        $basket = $this->basketRepository->get();
        $existing = $this->find($basket, $productId);
        if (empty($existing)) {
            throw new ProductNotFoundException("Basket does not contain product with id $productId");
        }

        if ($existing instanceof BillingItem) {
            throw new BasketException('Unable to update quantity of billing item');
        }
        $existing->quantity = $quantity;
        $this->basketRepository->save($basket);

        $this->recalculateBasketAction->handle();
    }
}