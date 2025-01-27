<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;

class LineItem
{

    /**
     * @param TaxRule[] $taxRules
     * @param array<int|string, string> $options
     * @param LineDiscount[] $discounts
     * @param array<int|string, string> $productData
     */
    public function __construct(
        public string $productId,
        public string $productName,
        public bool $taxable = false,
        public array $taxRules = [],
        public array $options = [],
        public array $discounts = [],
        public array $productData = [],
        public int $quantity = 1,
        public int $amount = 0,
        public int $discountAmount = 0,
        public int $subTotal = 0,
    )
    {

    }
}