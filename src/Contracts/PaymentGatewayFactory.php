<?php

namespace AltDesign\AltCommerce\Contracts;

interface PaymentGatewayFactory
{
    public function __construct(Resolver $resolver);

    /**
     * @param array<string, mixed> $config
     */
    public function create(string $name, string $currency, array $config): PaymentGateway;
}