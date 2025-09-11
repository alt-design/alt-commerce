<?php

namespace AltDesign\AltCommerce\Services\PriceCalculatorService\DataTransferObjects;

final readonly class PriceCalculationResponse
{
    public function __construct(
        public string $currency,
        public int $exclusiveAmount,
        public int $inclusiveAmount,
        public int $taxAmount,
        public bool $taxApplied,
    )
    {

    }
}