<?php

namespace AltDesign\AltCommerce\Commerce\Shipping;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Support\Price;

class ShippingRate
{
    /**
     * @param string $id
     * @param string $name
     * @param Price $price
     * @param TaxRule[]|null $taxRules
     */
    public function __construct(
        public string $id,
        public string $name,
        public Price $price,
        public array|null $taxRules = null,
    ) {

    }
}