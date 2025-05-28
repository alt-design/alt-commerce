<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderFactory;
use AltDesign\AltCommerce\Contracts\OrderRepository;

class CreateOrderAction
{

    public function __construct(
        protected BasketContext $context,
        protected OrderRepository    $orderRepository,
        protected OrderFactory       $orderFactory,
    )
    {

    }

    /**
     * @param array<string, mixed> $additional
     */
    public function handle(
        Customer $customer,
        Address|null $billingAddress = null,
        Address|null $shippingAddress = null,
        array $additional = [],
        \DateTimeImmutable|null $orderDate = null
    ): Order
    {
        $basket = $this->context->current();

        $order = $this->orderRepository->findByBasketId($basket->id);

        $orderId = $order?->id;
        $orderNumber  = $order ? $order->orderNumber : $this->orderRepository->reserveOrderNumber();

        $order = $this->orderFactory->createFromBasket(
            orderNumber: $orderNumber,
            basket: $basket,
            customer: $customer,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            additional: $additional,
            orderId: $orderId,
            orderDate: $orderDate,
        );

        $this->orderRepository->save($order);

        return $order;
    }




}