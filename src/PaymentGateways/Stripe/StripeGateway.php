<?php

namespace AltDesign\AltCommerce\PaymentGateways\Stripe;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GenerateAuthTokenRequest;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use Ramsey\Uuid\Uuid;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    public function __construct(
        protected string $name,
        protected BasketManager $basketManager,
        protected StripeClient $client
    )
    {

    }

    public function processOrder(ProcessOrderRequest $request): Order
    {
        $paymentIntent = $this->client->paymentIntents->capture($request->gatewayPaymentNonce);

        $transaction = new Transaction(
            id: Uuid::uuid4(),
            type: TransactionType::SALE,
            status: match($paymentIntent->status) {
                'requires_payment_method' => TransactionStatus::PENDING,
                'requires_confirmation' => TransactionStatus::PENDING,
                'requires_action' => TransactionStatus::PENDING,
                'processing' => TransactionStatus::PENDING,
                'requires_capture' => TransactionStatus::PENDING,
                'canceled' => TransactionStatus::FAILED,
                'succeeded' => TransactionStatus::SETTLED,
            },
            currency: $paymentIntent->currency,
            amount: $paymentIntent->amount,
            createdAt: new \DateTimeImmutable(),
            rejectionReason: $paymentIntent->cancellation_reason,
            additional: $paymentIntent->toArray(),
            gateway: $this->name,
            gatewayId: $paymentIntent->id,
        );
        $request->order->transactions[] = $transaction;
        if ($transaction->status === TransactionStatus::FAILED) {
            throw new PaymentFailedException($transaction->rejectionReason ?? 'Unknown transaction failure');
        }

        return $request->order;
    }

    public function createPaymentNonceAuthToken(GenerateAuthTokenRequest $request): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => $this->basketManager->total(),
            'currency' => $this->basketManager->currency(),
            'capture_method' => 'manual',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan
    {
        throw new \Exception('Not implemented');
    }
}