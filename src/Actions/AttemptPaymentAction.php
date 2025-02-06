<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Contracts\OrderRepository;

class AttemptPaymentAction
{

    public function __construct(
        protected OrderRepository    $orderRepository,
        protected GatewayBroker      $gatewayBroker,
    )
    {

    }

    public function handle(Order $order, string $paymentNonce): void
    {
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