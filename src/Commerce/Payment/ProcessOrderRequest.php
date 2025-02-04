<?php

namespace AltDesign\AltCommerce\Commerce\Payment;



use AltDesign\AltCommerce\Commerce\Order\Order;

class ProcessOrderRequest
{
    public function __construct(
        public Order $order,
        public string $gatewayName,
        public string $gatewayPaymentNonce,
    )
    {
    }
}