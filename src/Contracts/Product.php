<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Support\PriceCollection;

interface Product
{
    public function id(): string;

    public function name(): string;

    public function prices(): PriceCollection;

    public function price(): PricingSchema;

    public function taxable(): bool;

    /**
     * @return TaxRule[]
     */
    public function taxRules(): array;

    /**
     * @return array<mixed>
     */
    public function data(): array;


}