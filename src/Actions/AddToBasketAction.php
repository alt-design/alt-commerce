<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Traits\InteractWithBasket;


class AddToBasketAction
{
    use InteractWithBasket;

    public function __construct(
        protected BasketRepository $basketRepository,
        protected ProductRepository $productRepository,
        protected RecalculateBasketAction $recalculateBasketAction,
    )
    {

    }

    /**
     * @param string $productId
     * @param int $quantity
     * @param array<string, string> $options
     * @return void
     * @throws ProductNotFoundException
     */
    public function handle(string $productId, int $quantity = 1, array $options = []): void
    {
        $basket = $this->basketRepository->get();

        if ($existing = $this->find($basket, $productId)) {
            $existing->quantity += $quantity;
        } else {
            $product = $this->productRepository->find($productId);

            if (empty($product)) {
                throw new ProductNotFoundException($productId);
            }

            $basket->lineItems[] = new LineItem(
                product: $product,
                quantity: $quantity,
                options: $options
            );
        }
        $this->basketRepository->save($basket);

        $this->recalculateBasketAction->handle();
    }
}