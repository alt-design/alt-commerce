<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Exceptions\BillingPlanNotFoundException;
use AltDesign\AltCommerce\Support\Duration;

class RecurrentBillingSchema implements PricingSchema
{

    /**
     * @param BillingPlan[] $plans
     */
    public function __construct(protected array $plans)
    {

    }

    public function hasBillingPlan(): bool
    {
        return true;
    }

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return int
     * @throws BillingPlanNotFoundException
     * @throws \AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException
     */
    public function getAmount(string $currency, array $context = []): int
    {
        return $this->getBillingPlan($currency, $context)->prices->getAmount($currency);
    }

    public function isCurrencySupported(string $currency): bool
    {
        $currencies = [];
        foreach ($this->plans as $plan) {
            foreach ($plan->prices as $price) {
                $currencies[] = $price->currency;
            }
        }
        return in_array(strtoupper($currency), array_unique($currencies));
    }

    /**
     * @param string $currency
     * @param array<string, mixed> $context
     * @return BillingPlan
     * @throws BillingPlanNotFoundException
     */
    public function getBillingPlan(string $currency, array $context = []): BillingPlan
    {
        $planId = $context['plan'] ?? 'default';
        foreach ($this->plans as $plan) {
            if ($plan->id === $planId) {
                return $plan;
            }
        }
        throw new BillingPlanNotFoundException('Billing plan "' . $planId . '" not found.');
    }

    /**
     * @param string $currency
     * @return BillingPlan[]
     */
    public function getSupportedPlans(string $currency): array
    {
        return array_values(array_filter($this->plans, fn(BillingPlan $plan) => $plan->prices->isCurrencySupported($currency)));
    }

    public function minimumBillingInterval(string $currency): Duration
    {
        $billingIntervals = array_map(fn(BillingPlan $plan) => $plan->billingInterval, $this->getSupportedPlans($currency));

        usort($billingIntervals, fn(Duration $a, Duration $b) => $a->days() <=> $b->days());

        return $billingIntervals[0] ?? throw new BillingPlanNotFoundException('Unable to get minimum billing period');
    }

    public function cheapest(string $currency): BillingPlan
    {
        return $this->sortByRelativePrice($currency)[0] ?? throw new \Exception('Unable to determine cheapest billing plan');
    }

    public function mostExpensive(string $currency): BillingPlan
    {
        $results = $this->sortByRelativePrice($currency);
        if ($end = end($results)) {
            return $end;
        }

        throw new \Exception('Unable to determine most expensive billing plan');
    }

    /**
     * @param string $currency
     * @return BillingPlan[]
     * @throws BillingPlanNotFoundException
     */
    protected function sortByRelativePrice(string $currency): array
    {

        $minimumBillingInterval = $this->minimumBillingInterval($currency);
        $supported = $this->getSupportedPlans($currency);
        usort(
            $supported,
            fn(BillingPlan $a, BillingPlan $b) =>
                $a->relativePrice($currency, $minimumBillingInterval) <=> $b->relativePrice($currency, $minimumBillingInterval)
        );

        return $supported;
    }
}