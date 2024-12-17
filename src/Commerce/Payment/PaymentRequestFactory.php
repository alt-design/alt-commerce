<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Commerce\Order\Order;

class PaymentRequestFactory
{
    /**
     * @param Order $order
     * @param string $token
     * @param array<string, string> $additional
     * @return PaymentRequest
     */
    public function createFromOrder(Order $order, string $token, array $additional = []): PaymentRequest
    {
        return new PaymentRequest(
            token: $token,
             currency: $order->currency,
             orderNumber: $order->orderNumber,
             billingAddress: $order->billingAddress,
             total: $order->total,
             additional: $additional
        );
    }
}