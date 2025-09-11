<?php

namespace AltDesign\AltCommerce\Services\PriceCalculatorService;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Services\PriceCalculatorService\DataTransferObjects\PriceCalculationResponse;

class Service
{

    public function __construct(protected float $defaultTaxRate = 0.2)
    {

    }

    /**
     * @param TaxRule[] $taxRules
     */
    public function calculate(string $currency, int $amount, bool $amountInclusive, string $countryCode, array $taxRules): PriceCalculationResponse
    {
        $exclusiveAmount = $amountInclusive ? $amount / ($this->defaultTaxRate + 1) : $amount;
        $taxRule = $this->getApplicableTaxRule($taxRules, $countryCode);
        $taxAmount = $taxRule ? ($exclusiveAmount * $taxRule->rate / 100) : 0;
        $inclusiveAmount = $exclusiveAmount + $taxAmount;

        return new PriceCalculationResponse(
            currency: $currency,
            exclusiveAmount: $exclusiveAmount,
            inclusiveAmount: $inclusiveAmount,
            taxAmount: $taxAmount,
            taxApplied: !!$taxRule,
        );
    }

    protected function getApplicableTaxRule(array $taxRules, string $countryCode): ?TaxRule
    {
        $taxRules = array_filter($taxRules, fn(TaxRule $taxRule) => empty($taxRule->countryFilter) || in_array($countryCode, $taxRule->countryFilter));

        return $taxRules[0] ?? null;
    }
}