<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Enum\ProductType;

class LineItem
{

    /**
     * @param string $productId
     * @param string $productName
     * @param ProductType $productType
     * @param bool $taxable
     * @param TaxRule[] $taxRules
     * @param array<int|string, string> $options
     * @param LineDiscount[] $discounts
     * @param array<int|string, string> $productData
     * @param int $quantity
     * @param int $amount
     * @param int $discountAmount
     * @param int $subTotal
     */
    public function __construct(
        public string $productId,
        public string $productName,
        public ProductType $productType,
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