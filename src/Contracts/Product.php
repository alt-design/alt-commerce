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
     * Whether this product may be bought at all. Separate from stock: an
     * unpublished or withdrawn product is not purchasable at any quantity.
     */
    public function purchasable(): bool;

    /**
     * @return TaxRule[]
     */
    public function taxRules(): array;

    /**
     * @return array<mixed>
     */
    public function data(): array;
}
