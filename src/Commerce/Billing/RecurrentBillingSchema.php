<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Exceptions\BillingPlanNotFound;
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
     * @throws BillingPlanNotFound
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
     * @throws BillingPlanNotFound
     */
    public function getBillingPlan(string $currency, array $context = []): BillingPlan
    {
        $planId = $context['plan'] ?? 'default';
        foreach ($this->plans as $plan) {
            if ($plan->id === $planId) {
                return $plan;
            }
        }
        throw new BillingPlanNotFound('Billing plan "' . $planId . '" not found.');
    }

    public function minimumBillingInterval(string $currency): Duration
    {

        $billingIntervals = array_map(
            fn(BillingPlan $plan) => $plan->billingInterval,
            array_filter(
                $this->plans, fn(BillingPlan $plan) => $plan->prices->isCurrencySupported($currency)
            )
        );

        usort($billingIntervals, fn(Duration $a, Duration $b) => $a->days() <=> $b->days());

        return $billingIntervals[0] ?? throw new \Exception('Unable to get minimum billing period');
    }

    public function cheapest(string $currency): int
    {
        return $this->pricingRange($currency)['min'];
    }

    public function mostExpensive(string $currency): int
    {
        return $this->pricingRange($currency)['max'];
    }

    public function hasRange(string $currency): bool
    {
        return $this->cheapest($currency) !== $this->mostExpensive($currency);
    }

    /**
     * @param string $currency
     * @return array{min: int, max: int}
     * @throws \AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException
     */
    protected function pricingRange(string $currency): array
    {
        $minimumInterval = $this->minimumBillingInterval($currency);
        $results = [
            'min' => 0,
            'max' => 0,
        ];

        foreach ($this->plans as $plan) {
            if (!$plan->prices->isCurrencySupported($currency)) {
                continue;
            }

            $amount = Duration::convert(
                amount: $plan->prices->getAmount($currency),
                from: $plan->billingInterval,
                to: $minimumInterval
            );

            if (empty($results['min']) || $amount < $results['min']) {
                $results['min'] = $amount;
            }

            if (empty($results['max']) || $amount > $results['max']) {
                $results['max'] = $amount;
            }
        }

        return $results;
    }



}