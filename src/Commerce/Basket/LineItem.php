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
        public string $id,
        public string $productId,
        public string $productName,
        public int    $amount,
        public int    $quantity = 1,
        public bool   $taxable = false,
        public array  $taxRules = [],
        public array  $options = [],
        public array  $discounts = [],
        public array  $productData = [],
        public int    $discountTotal = 0,
        public int    $subTotal = 0,
        public int    $taxTotal = 0,
        public float  $taxRate = 0,
        public string|null $taxName = null
    )
    {

    }
}