<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\RuleEngine\Rules\ShippingCountryConstraintRule;
use PHPUnit\Framework\TestCase;

class ShippingCountryConstraintRuleTest extends TestCase
{
    public function test_passes(): void
    {
        $rule = new ShippingCountryConstraintRule(
            countryCodes: ['GB']
        );

        $context = [
            'shippingAddress' => new Address(
                countryCode: 'GB'
            ),
        ];

        $this->assertTrue($rule->evaluate($context)->result);
    }

    public function test_fails(): void
    {
        $rule = new ShippingCountryConstraintRule(
            countryCodes: ['GB']
        );

        $context = [
            'shippingAddress' => new Address(
                countryCode: 'US'
            ),
        ];

        $this->assertFalse($rule->evaluate($context)->result);
    }
}