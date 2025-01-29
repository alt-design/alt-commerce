<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Support\Duration;

class BillingItem
{
    /**
     * @param array<string, mixed> $additional
     */
    public function __construct(
        public string $productId,
        public string $productName,
        public string $planId,
        public int $amount,
        public Duration $billingInterval,
        public Duration|null $trialPeriod = null,
        public array $additional = [],
    )
    {

    }
}