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
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return string
     */
    public function saveCustomer(Customer $customer, array $data): string;

    public function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string;

    public function createBillingPlan(BillingPlan $billingPlan): string;

    public function updateBillingPlan(string $id, BillingPlan $billingPlan): void;

    public function createSubscription(CreateSubscriptionRequest $request): Subscription;

    public function createCharge(CreatePaymentRequest $request): Transaction;

}