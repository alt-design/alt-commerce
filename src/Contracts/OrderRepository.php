<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Order\Order;

interface OrderRepository
{
    public function save(Order $order): void;
}