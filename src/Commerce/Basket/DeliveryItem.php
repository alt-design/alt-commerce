<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class DeliveryItem
{
    /**
     * @param \AltDesign\AltCommerce\Commerce\Tax\TaxRule[] $taxRules
     *   Tax rules to charge on the (tax-exclusive) delivery amount — typically
     *   the rules of the goods being delivered. Empty = untaxed delivery.
     */
    public function __construct(
        public string $name,
        public int $amount,
        public array $taxRules = [],
    ) {

    }

}