<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketSubTotalConstraintRule;

use PHPUnit\Framework\TestCase;

final class BasketSubTotalConstraintRuleTest extends TestCase
{

    protected $basket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = new Basket(
            id: 'basket-id',
            currency: 'GBP',
            countryCode: 'GB',
            subTotal: 0
        );
    }

    public function test_passes_with_min_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            min: 1900
        );

        $this->basket->subTotal = 2000;

        $this->assertTrue($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_fails_with_min_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            min: 2100
        );

        $this->basket->subTotal = 2000;

        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_passes_with_max_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            max: 2100
        );

        $this->basket->subTotal = 2000;

        $this->assertTrue($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_failed_with_max_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            max: 1900
        );

        $this->basket->subTotal = 2000;

        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_passes_with_min_and_max_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            min: 1900,
            max: 2100
        );

        $this->basket->subTotal = 2000;

        $this->assertTrue($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_failed_with_min_and_max_amount(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'GBP',
            min: 1500,
            max: 1700
        );

        $this->basket->subTotal = 2000;

        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_failed_with_different_currency(): void
    {
        $rule = new BasketSubTotalConstraintRule(
            currency: 'USD',
            min: 1900
        );

        $this->basket->subTotal = 2000;

        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);

    }



}