<?php

namespace AltDesign\AltCommerce\PaymentGateways\FakeGateway;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Commerce\Payment\CreatePaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\CreateSubscriptionRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\PaymentGatewayDriver;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;

class FakeGateway implements PaymentGateway, PaymentGatewayDriver, PaymentGatewayFactory
{

    public function createPaymentNonceAuthToken(): string
    {
        return 'fake-nonce-token';
    }

    public function saveCustomer(Customer $customer, array $data): string
    {
        return 'fake-customer-id';
    }

    public function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string
    {
        return 'fake-payment-method-id';
    }

    public function createSubscription(CreateSubscriptionRequest $request): Subscription
    {
        throw new \Exception('Gateway not implemented');
    }

    public function createCharge(CreatePaymentRequest $request): Transaction
    {
        throw new \Exception('Gateway not implemented');
    }

    public function createBillingPlan(BillingPlan $billingPlan): string
    {
        return 'fake-billing-plan-id';
    }

    public function updateBillingPlan(string $id, BillingPlan $billingPlan) : void
    {

    }

    public function name(): string
    {
        return 'null';
    }

    public function factory(): PaymentGatewayFactory
    {
        return new self();
    }

    public function create(string $currency, array $config): PaymentGateway
    {
        return new self();
    }
}