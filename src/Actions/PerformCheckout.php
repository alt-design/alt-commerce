<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\CreatePaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\CreateSubscriptionRequest;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;

class PerformCheckout
{

    public function __construct(
        protected BasketRepository   $basketRepository,
        protected SettingsRepository $settingsRepository,
        protected OrderRepository    $orderRepository,
        protected OrderFactory       $orderFactory,
        protected GatewayBroker      $gatewayBroker,
        protected EmptyBasketAction  $emptyBasketAction
    )
    {

    }

    /**
     * @param Customer $customer
     * @param string $paymentNonce
     * @param array<string, mixed> $additional
     * @return Order
     * @throws PaymentFailedException
     */
    public function handle(Customer $customer, string $paymentNonce, array $additional = []): Order
    {
        $order = $this->getOrder($customer, $additional);
        try {
            $this->attemptPayment($order, $paymentNonce);

            $this->emptyBasketAction->handle();
        } finally {

            $this->orderRepository->save($order);
        }

        return $order;

    }

    protected function attemptPayment(Order $order, string $paymentNonce): void
    {
        $config = $this->gatewayBroker->currency($order->currency);
        $gateway = $config->gateway();
        $gatewayCustomerId = $gateway->saveCustomer($order->customer, []);
        $gatewayPaymentMethodToken = $gateway->createPaymentMethod($gatewayCustomerId, $paymentNonce);

        if (!empty($order->total)) {
            $transaction = $gateway->createCharge(
                new CreatePaymentRequest(
                    gatewayPaymentMethodToken: $gatewayPaymentMethodToken,
                    gatewayCustomerId: $gatewayCustomerId,
                    amount: $order->total,
                    descriptor: $this->getStatementDescriptor($order->orderNumber),
                    billingAddress: $order->billingAddress
                )
            );
            if ($transaction->status === TransactionStatus::SETTLED) {
                $order->outstanding = 0;
                $order->status = OrderStatus::PROCESSING;
            }

            $order->transactions[] = $transaction;

            if ($transaction->status === TransactionStatus::FAILED) {
                throw new PaymentFailedException($transaction->rejectionReason ?? 'Unknown transaction failure');
            }
        }

        foreach ($order->billingItems as $item) {
            $order->subscriptions[] = $gateway->createSubscription(
                new CreateSubscriptionRequest(
                    gatewayPaymentMethodToken: $gatewayPaymentMethodToken,
                    gatewayCustomerId: $gatewayCustomerId,
                    gatewayPlanId: $item->getGatewayId($config->driver()),
                )
            );
        }
    }

    /**
     * @param Customer $customer
     * @param array<string, mixed> $additional
     * @return Order
     * @throws PaymentFailedException
     */
    protected function getOrder(Customer $customer, array $additional): Order
    {
        $basket = $this->basketRepository->get();
        $order = $this->orderRepository->findByBasketId($basket->id);
        if (!$order) {
            $order = $this->orderFactory->createFromBasket(
                basket: $basket,
                customer: $customer,
                additional: $additional
            );
        }

        // ensure order is correct status for attempting payment
        if ($order->status !== OrderStatus::DRAFT) {
            throw new PaymentFailedException('Unable to take payment for non draft orders');
        }

        return $order;
    }

    protected function getStatementDescriptor(string $orderNumber): string
    {
        $settings = $this->settingsRepository->get();
        $replacements = [
            '{tradingName}' => $settings->tradingName,
            '{orderNumber}' => $orderNumber
        ];

        $description =  str_replace(array_keys($replacements), array_values($replacements), $settings->statementDescriptor);
        return substr($description, 0, 22);
    }
}