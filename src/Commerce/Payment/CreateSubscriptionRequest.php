<?php

namespace AltDesign\AltCommerce\Commerce\Payment;


use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;

class CreateSubscriptionRequest
{
    public function __construct(
        public string $gatewayPaymentMethodToken,
        public string $gatewayCustomerId,
        public BillingPlan $billingPlan,
    )
    {
    }
}