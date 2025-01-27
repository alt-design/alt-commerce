<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketHasProductRule;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class BasketHasProductRuleTest extends TestCase
{
    use CommerceHelper;

    protected $basket;
    protected $product;

    protected function setUp(): void
    {
        $this->basket = new Basket(
            id: 'basket-id',
            currency: 'GBP',
            countryCode: 'GB',
            subTotal: 0
        );

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
        $rule = new BasketHasProductRule([54321]);
        $this->assertTrue($rule->evaluate(['basket' => $this->basket])->result);
    }

    public function test_fails(): void
    {
        $rule = new BasketHasProductRule([12345]);
        $this->assertFalse($rule->evaluate(['basket' => $this->basket])->result);
    }
}