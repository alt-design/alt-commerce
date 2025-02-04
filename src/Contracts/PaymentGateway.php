<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;

interface PaymentGateway
{
    public function createPaymentNonceAuthToken(): string;

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan;

    public function processOrder(ProcessOrderRequest $request): Order;

}