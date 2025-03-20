<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;

interface PaymentGateway
{
    public function createPaymentNonceAuthToken(): string;

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan;

    /**
     * @param ProcessOrderRequest $request
     * @return Order
     * @throws PaymentFailedException
     * @throws PaymentGatewayException
     */
    public function processOrder(ProcessOrderRequest $request): Order;

}