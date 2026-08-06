<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Enum\StockPolicy;

interface Product
{
    public function id(): string;

    public function name(): string;

    public function price(): PricingSchema;

    public function taxable(): bool;

    public function stockPolicy(): StockPolicy;

    /**
     * @return TaxRule[]
     */
    public function taxRules(): array;

    /**
     * @return array<mixed>
     */
    public function data(): array;
}
