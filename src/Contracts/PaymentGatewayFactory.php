<?php

namespace AltDesign\AltCommerce\Contracts;

interface PaymentGatewayFactory
{
    /**
     * @param string $currency
     * @param array<string, mixed> $config
     * @return PaymentGateway
     */
    public function create(string $currency, array $config): PaymentGateway;
}