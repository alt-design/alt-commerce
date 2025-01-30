<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;

interface ProductRepository
{
    public function find(string $productId): ?Product;

    public function saveBillingPlan(string $productId, BillingPlan $billingPlan): void;
}