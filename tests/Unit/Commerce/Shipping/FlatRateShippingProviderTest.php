<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Shipping;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Shipping\FlatRateShippingMethod;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Tests\Support\AddressFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class FlatRateShippingProviderTest extends TestCase
{
    public function test_calculate_price_returns_fixed_price()
    {
        $method = new FlatRateShippingMethod(
            id: 'flat-rate-shipping',
            name: 'Flat Rate Shipping',
            price: new Price(250, 'GBP'),
            ruleGroup: Mockery::mock(RuleGroup::class),
        );

        $this->assertEquals('GBP', $method->currency());
        $this->assertEquals(250, $method->calculatePrice(
            Mockery::mock(Basket::class),
            AddressFactory::create(),
        ));
        $this->assertEquals('flat-rate-shipping', $method->id());
        $this->assertEquals('Flat Rate Shipping', $method->name());
    }
}