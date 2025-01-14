<?php

namespace AltDesign\AltCommerce\Commerce\Tax;

class TaxRule
{
    /**
     * @param string $name
     * @param int $rate
     * @param string[] $countryFilter
     */
    public function __construct(
        public string $name,
        public int    $rate,
        public array  $countryFilter = [],
    )
    {

    }
}