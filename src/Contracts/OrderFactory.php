<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Order\Order;

interface OrderFactory
{
    /**
     * @param array<string, mixed> $additional
     */
    public function createFromBasket(
        string $orderNumber,
        Basket $basket,
        Customer $customer,
        array $additional = [],
        string|null $orderId = null,
        \DateTimeImmutable|null $orderDate = null,
    ): Order;
}