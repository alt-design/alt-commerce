<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\PaymentGateway;

class GatewayConfig
{
    public function __construct(
        protected string $name,
        protected string $driver,
        protected PaymentGateway $gateway)
    {

    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function gateway(): PaymentGateway
    {
        return $this->gateway;
    }
}