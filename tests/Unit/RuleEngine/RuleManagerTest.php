<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Enum\RuleMatchingType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\RuleEngine\RuleManager;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketSubTotalConstraintRule;
use AltDesign\AltCommerce\RuleEngine\Rules\ShippingCountryConstraintRule;
use AltDesign\AltCommerce\Tests\Support\AddressFactory;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RuleManagerTest extends TestCase
{
    public function test_rule_manager(): void
    {
        $group = new RuleGroup(
            rules: [
                new RuleGroup(
                    rules: [
                        new ShippingCountryConstraintRule(['GB']),
                        new BasketSubTotalConstraintRule('GBP', 8000),
                    ],
                    matchingType: RuleMatchingType::ALL
                ),
                new BasketSubTotalConstraintRule('GBP', 10000)
            ],
            matchingType: RuleMatchingType::ANY
        );

        $basket = Mockery::mock(Basket::class);
        $basket->subTotal = 8000;
        $basket->currency = 'GBP';

        $ruleManager = new RuleManager();
        $result = $ruleManager->evaluate(
            ruleGroup: $group,
            context: [
                'basket' => $basket,
                'shippingAddress' => AddressFactory::create(['countryCode' => 'GB'])
            ]
        );
        $this->assertTrue($result->result);
    }
}