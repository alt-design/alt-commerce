<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;

interface BillingPlanRepository
{
    public function find(string $id): ?BillingPlan;
}