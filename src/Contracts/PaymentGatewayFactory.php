<?php

namespace AltDesign\AltCommerce\Contracts;

interface PaymentGatewayFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(string $name, string $currency, array $config): PaymentGateway;
}