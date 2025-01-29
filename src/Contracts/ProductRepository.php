<?php

namespace AltDesign\AltCommerce\Contracts;

interface ProductRepository
{
    public function find(string $productId): ?Product;

    public function saveGatewayIdForBillingPlan(string $productId, string $planId, string $currency, string $gateway, string $gatewayId): void;

    public function getGatewayIdForBillingPlan(string $productId, string $planId, string $currency, string $gateway): string;
}