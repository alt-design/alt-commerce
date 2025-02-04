<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Commerce\Payment\GatewayBroker;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;

class AttemptPayment
{

    public function __construct(
        protected BasketRepository   $basketRepository,
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

            $order = $this->attemptPayment($order, $paymentNonce);

            $this->emptyBasketAction->handle();

        } finally {

            $this->orderRepository->save($order);
        }

        return $order;
    }

    protected function attemptPayment(Order $order, string $paymentNonce): Order
    {
        $config = $this->gatewayBroker->currency($order->currency);
        $gateway = $config->gateway();
        return $gateway->processOrder(
            new ProcessOrderRequest(
                order: $order,
                gatewayName: $config->name(),
                gatewayPaymentNonce: $paymentNonce,
            )
        );
    }

    /**
     * @param array<string, mixed> $additional
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

}