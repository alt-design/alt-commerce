<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
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
     * @throws CurrencyNotSupportedException
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

            if (!$product->prices()->supports($basket->currency)) {
                throw new CurrencyNotSupportedException();
            }

            $basket->lineItems[] = new LineItem(
                productId: $product->id(),
                productName: $product->name(),
                productType: $product->type(),
                taxable: $product->taxable(),
                taxRules: $product->taxRules(),
                options: $options,
                productData: $product->data(),
                quantity: $quantity,
                subTotal: $product->prices()->currency($basket->currency),
            );
        }

        $this->recalculateBasketAction->handle();
    }
}