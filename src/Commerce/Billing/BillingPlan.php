<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\PriceCollection;

class BillingPlan
{
    public function __construct(
        public string $id,
        public PriceCollection $prices,
        public Duration $billingInterval,
        public Duration|null $trialPeriod = null,
    )
    {

    }
}