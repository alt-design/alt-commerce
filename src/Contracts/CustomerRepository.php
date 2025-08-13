<?php

namespace AltDesign\AltCommerce\Contracts;

interface CustomerRepository
{
    public function find(string $customerId): ?Customer;

    public function findGatewayId(string $customerId, string $gatewayName): ?string;

    public function setGatewayId(string $customerId, string $gatewayName, string $gatewayId): void;
}