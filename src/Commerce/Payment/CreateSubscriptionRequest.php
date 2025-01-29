<?php

namespace AltDesign\AltCommerce\Commerce\Payment;



class CreateSubscriptionRequest
{
    public function __construct(
        public string $gatewayPaymentMethodToken,
        public string $gatewayCustomerId,
        public string $gatewayPlanId,
    )
    {
    }
}