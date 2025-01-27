<?php

namespace AltDesign\AltCommerce\Commerce\Shipping;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Support\Money;

class ShippingRate
{
    /**
     * @param string $id
     * @param string $name
     * @param Money $price
     * @param TaxRule[]|null $taxRules
     */
    public function __construct(
        public string     $id,
        public string     $name,
        public Money      $price,
        public array|null $taxRules = null,
    ) {

    }
}