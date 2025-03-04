<?php

namespace AltDesign\AltCommerce\PaymentGateways\Mock;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;

class MockGateway implements PaymentGateway
{

    public function createPaymentNonceAuthToken(): string
    {
        return 'mock-auth-token';
    }

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan
    {
        throw new \Exception('Not implemented');
    }

    public function processOrder(ProcessOrderRequest $request): Order
    {

        $order = $request->order;
        $order->outstanding = 0;
        $order->transactions[] = new Transaction(
            id: 'test-payment',
            type: TransactionType::CREDIT,
            status: TransactionStatus::SETTLED,
            currency: 'GBP',
            amount: $request->order->total,
            createdAt: new \DateTimeImmutable(),
            additional: [
                'all_the' => 'good_stuff'
            ],
            gateway: 'mock',
            gatewayId: 'mock-payment-id',
        );

        return $request->order;
    }
}