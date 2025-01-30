<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Commerce\Payment\CreatePaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\CreateSubscriptionRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;

interface PaymentGateway
{
    public function createPaymentNonceAuthToken(): string;

    /**
     * @param array<string, mixed> $data
     */
    public function saveCustomer(Customer $customer, array $data): string;

    public function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string;

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan;

    public function createSubscription(CreateSubscriptionRequest $request): Subscription;

    public function createCharge(CreatePaymentRequest $request): Transaction;

}