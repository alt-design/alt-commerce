<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Commerce\Payment\PaymentRequest;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\PaymentProviderRepository;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;

class AttemptPaymentAction
{
    public function __construct(
        protected BasketRepository $basketRepository,
        protected PaymentProviderRepository $paymentProviderRepository,
        protected OrderFactory $orderFactory,
        protected OrderRepository $orderRepository,
        protected SettingsRepository $settingsRepository,
    )
    {

    }

    /**
     * @param string $provider
     * @param string $token
     * @param Customer $customer
     * @param array<string, string> $additional
     * @return Order
     * @throws PaymentFailedException
     */
    public function handle(string $provider, string $token, Customer $customer, array $additional = []): Order
    {
        $provider = $this->paymentProviderRepository->find($provider);
        if (empty($provider)) {
            throw new PaymentFailedException('Payment provider not found');
        }

        $basket = $this->basketRepository->get();
        $order = $this->orderFactory->createFromBasket($basket, $customer, $additional);

        if (empty($order->billingAddress)) {
            throw new PaymentFailedException('Billing address is required');
        }

        $request = new PaymentRequest(
            token: $token,
            currency: $basket->currency,
            orderNumber: $order->orderNumber,
            billingAddress: $order->billingAddress,
            total: $order->total,
        );

        $transaction = $provider->attemptPayment($request);
        $order->transactions[] = $transaction;

        if ($transaction->status === TransactionStatus::SETTLED) {
            $order->outstanding = 0;
            $order->status = OrderStatus::PROCESSING;
        }

        $this->orderRepository->save($order);

        if ($transaction->status === TransactionStatus::FAILED) {
            throw new PaymentFailedException($transaction->rejectionReason ?? 'Unknown transaction failure');
        }

        // Clear the basket
        $this->basketRepository->delete();

        return $order;

    }

}