<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketDoesNotHaveProductRule;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class BasketDoesNotHaveProductRuleTest extends TestCase
{
    use CommerceHelper;

    protected $product;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->product = $this->createProduct(
            id: 54321,
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(10000, 'GBP')
                ])
            )
        );
        $this->addLineItemToBasket($this->product, 1);
    }

    public function test_passes(): void
    {
        $rule = new BasketDoesNotHaveProductRule([12345]);
        $this->assertTrue($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_fails(): void
    {
        $rule = new BasketDoesNotHaveProductRule([54321]);
        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);
    }
}