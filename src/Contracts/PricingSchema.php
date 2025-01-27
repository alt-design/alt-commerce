<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;

interface PricingSchema
{
    public function hasBillingPlan(): bool;

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return int
     */
    public function getAmount(string $currency, array $context = []): int;

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return BillingPlan
     */
    public function getBillingPlan(string $currency, array $context = []): BillingPlan;

    public function isCurrencySupported(string $currency): bool;

}