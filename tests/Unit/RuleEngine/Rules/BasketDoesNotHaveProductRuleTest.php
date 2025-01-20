<?php

namespace AltDesign\AltCommerce\Tests\Unit\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketDoesNotHaveProductRule;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use PHPUnit\Framework\TestCase;

class BasketDoesNotHaveProductRuleTest extends TestCase
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

        $this->product = $this->createProductMock(54321, priceCollection: new PriceCollection([new Price(10000, 'GBP')]));
        $this->addProductToBasket($this->product, 1);
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