<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Exceptions\OrderAlreadyPlacedException;

class AttemptPaymentAction
{

    public function __construct(
        protected OrderRepository    $orderRepository,
        protected GatewayBroker      $gatewayBroker,
    )
    {

    }

    /**
     * @throws OrderAlreadyPlacedException
     */
    public function handle(Order $order, string $paymentNonce): void
    {
        if (! $order->isUnpaidDraft()) {
            throw new OrderAlreadyPlacedException("Order {$order->orderNumber} has already been placed or paid");
        }

        try {
            $config = $this->gatewayBroker->currency($order->currency);
            $gateway = $config->gateway();
            $order = $gateway->processOrder(
                new ProcessOrderRequest(
                    order: $order,
                    gatewayName: $config->name(),
                    gatewayPaymentNonce: $paymentNonce,
                )
            );

        } finally {
            $this->orderRepository->save($order);
        }

    }
}