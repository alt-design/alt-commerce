<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;

class SaveBillingPlan
{
    public function __construct(protected GatewayBroker $gatewayBroker)
    {

    }

    public function handle(BillingPlan $billingPlan): void
    {
        foreach ($billingPlan->prices as $price) {
            $this->gatewayBroker
                ->currency($price->currency)
                ->gateway()
                ->saveBillingPlan($billingPlan);
        }
    }
}