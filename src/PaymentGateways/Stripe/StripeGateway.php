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
use AltDesign\AltCommerce\Exceptions\PaymentOutcomeUnknownException;
use Ramsey\Uuid\Uuid;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\ExceptionInterface;
use Stripe\Exception\IdempotencyException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{

    const ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF',
        'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND',
        'VUV', 'XAF', 'XOF', 'XPF'
    ];

    public function __construct(
        protected string $name,
        protected BasketManager $basketManager,
        protected StripeClient $client
    )
    {

    }

    public function processOrder(ProcessOrderRequest $request): Order
    {
        $this->guardAgainstMismatchedPaymentIntent($request);

        $paymentIntent = $this->capture($request->gatewayPaymentNonce);

        $transactionAmount = in_array($paymentIntent->currency, self::ZERO_DECIMAL_CURRENCIES) ?
            $paymentIntent->amount * 100 :
            $paymentIntent->amount;

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
                default => throw new PaymentOutcomeUnknownException("Unexpected Stripe payment intent status: {$paymentIntent->status}"),
            },
            currency: $paymentIntent->currency,
            amount: $transactionAmount,
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

    /**
     * The nonce names an intent created earlier in the checkout and arrives
     * from the caller, so it is checked against the order before any money
     * moves. Without this, an intent raised for a cheaper basket captures
     * happily against an expensive order.
     *
     * @throws PaymentFailedException
     */
    protected function guardAgainstMismatchedPaymentIntent(ProcessOrderRequest $request): void
    {
        $paymentIntent = $this->client->paymentIntents->retrieve($request->gatewayPaymentNonce);

        if (strtolower($paymentIntent->currency) !== strtolower($request->order->currency)) {
            throw new PaymentFailedException('Payment currency does not match the order');
        }

        if ($paymentIntent->amount !== $this->orderAmount($request->order)) {
            throw new PaymentFailedException('Payment amount does not match the order total');
        }

        if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
            throw new PaymentFailedException('Payment has already been captured');
        }
    }

    /**
     * Stripe can take the money and its answer still not arrive, so anything
     * short of a rejection is checked before it is reported.
     *
     * @throws ApiErrorException
     * @throws PaymentOutcomeUnknownException
     */
    protected function capture(string $paymentIntentId): PaymentIntent
    {
        try {
            return $this->client->paymentIntents->capture($paymentIntentId);
        } catch (ExceptionInterface $exception) {
            if ($this->isRejection($exception)) {
                throw $exception;
            }

            return $this->confirmCaptured($paymentIntentId, $exception);
        }
    }

    protected function isRejection(ExceptionInterface $exception): bool
    {
        if (! $exception instanceof ApiErrorException) {
            return false;
        }

        if ($exception instanceof IdempotencyException) {
            return false;
        }

        return intdiv((int) $exception->getHttpStatus(), 100) === 4;
    }

    /**
     * @throws PaymentOutcomeUnknownException
     */
    protected function confirmCaptured(string $paymentIntentId, ExceptionInterface $captureError): PaymentIntent
    {
        try {
            $paymentIntent = $this->client->paymentIntents->retrieve($paymentIntentId);
        } catch (ExceptionInterface) {
            $paymentIntent = null;
        }

        if ($paymentIntent?->status !== PaymentIntent::STATUS_SUCCEEDED) {
            throw new PaymentOutcomeUnknownException("Stripe did not confirm whether {$paymentIntentId} was captured", previous: $captureError);
        }

        return $paymentIntent;
    }

    /**
     * The order total in the units Stripe expects, mirroring amount().
     */
    protected function orderAmount(Order $order): int
    {
        return in_array($order->currency, self::ZERO_DECIMAL_CURRENCIES) ?
            intdiv($order->total, 100) :
            $order->total;
    }

    public function createPaymentNonceAuthToken(GenerateAuthTokenRequest $request): PaymentIntent
    {
        $payload = [
            'amount' => $this->amount(),
            'currency' => $this->basketManager->currency(),
            'capture_method' => 'manual',
            'description' => $request->description,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ];

        // Map customer details onto Stripe's native fields when provided.
        if ($request->customerEmail) {
            $payload['receipt_email'] = $request->customerEmail;
        }

        if ($address = $request->shippingAddress) {
            $payload['shipping'] = array_filter([
                'name' => $request->customerName ?? $address->fullName,
                'phone' => $request->customerPhone ?? $address->phoneNumber,
                'address' => array_filter([
                    'line1' => $address->street,
                    'city' => $address->locality,
                    'state' => $address->region,
                    'postal_code' => $address->postalCode,
                    'country' => $address->countryCode ? substr($address->countryCode, 0, 2) : null,
                ]),
            ]);
        }

        return $this->client->paymentIntents->create($payload);
    }

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan
    {
        throw new \Exception('Not implemented');
    }

    protected function amount(): int
    {
        if (in_array($this->basketManager->currency(), self::ZERO_DECIMAL_CURRENCIES)) {
            return $this->basketManager->total() / 100;
        }
        return $this->basketManager->total();
    }
}