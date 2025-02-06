<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Order\OrderFactory;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderRepository;

class CreateOrderAction
{

    public function __construct(
        protected BasketRepository   $basketRepository,
        protected OrderRepository    $orderRepository,
        protected OrderFactory       $orderFactory,
    )
    {

    }

    /**
     * @param array<string, mixed> $additional
     */
    public function handle(Customer $customer, array $additional = []): Order
    {
        $basket = $this->basketRepository->get();

        $order = $this->orderRepository->findByBasketId($basket->id);

        $orderId = $order?->id;
        $orderNumber  = $order ? $order->id : $this->orderRepository->reserveOrderNumber();

        $order = $this->orderFactory->createFromBasket(
            orderNumber: $orderNumber,
            basket: $basket,
            customer: $customer,
            additional: $additional,
            orderId: $orderId,
        );

        $this->orderRepository->save($order);

        return $order;
    }




}