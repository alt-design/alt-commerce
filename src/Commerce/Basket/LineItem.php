<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\Product;

class LineItem
{

    /**
     * @param Product $product
     * @param int $quantity
     * @param array<int|string, string> $options
     * @param LineDiscount[] $discounts
     * @param int $amount
     * @param int $discountAmount
     * @param int $subTotal
     */
    public function __construct(
        public Product $product,
        public int $quantity = 1,
        public array $options = [],
        public array $discounts = [],
        public int $amount = 0,
        public int $discountAmount = 0,
        public int $subTotal = 0,
    )
    {

    }
}