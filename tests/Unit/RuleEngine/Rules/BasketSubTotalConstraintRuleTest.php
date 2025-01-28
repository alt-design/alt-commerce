<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\RuleEngine\Rules\BasketSubTotalConstraintRule;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

final class BasketSubTotalConstraintRuleTest extends TestCase
{
    use CommerceHelper;

    protected $basket;

    protected function setUp(): void
    {
        $this->createBasket();
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