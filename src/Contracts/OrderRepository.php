<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Order\Order;

interface OrderRepository
{
    public function save(Order $order): void;

    public function findByBasketId(string $basketId): ?Order;

    public function reserveOrderNumber(): string;
}