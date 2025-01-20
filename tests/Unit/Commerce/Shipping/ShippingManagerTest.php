<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Shipping;


use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Shipping\FlatRateShippingMethod;
use AltDesign\AltCommerce\Commerce\Shipping\ShippingManager;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ShippingMethodRepository;
use AltDesign\AltCommerce\Enum\RuleMatchingType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\RuleEngine\RuleManager;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketSubTotalConstraintRule;
use AltDesign\AltCommerce\RuleEngine\Rules\ShippingCountryConstraintRule;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Tests\Support\AddressFactory;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class ShippingManagerTest extends TestCase
{
    protected $shippingMethodRepository;
    protected $basket;
    protected $basketRepository;
    protected $shippingManager;

    protected function setup(): void
    {
        $this->shippingMethodRepository = Mockery::mock(ShippingMethodRepository::class);

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->currency = 'GBP';

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturns($this->basket);

        $this->shippingManager = new ShippingManager(
            shippingMethodRepository: $this->shippingMethodRepository,
            basketRepository: $this->basketRepository,
            ruleManager: new RuleManager(),
        );
    }


    public function test_get_available_rates()
    {

        $shippingMethod1 = new FlatRateShippingMethod(
            id: 'free-shipping-over-50-gbp',
            name: 'Free Postage',
            price: new Price(0, 'GBP'),
            ruleGroup: new RuleGroup(
                rules: [
                    new BasketSubTotalConstraintRule(currency: 'GBP', min: 5000),
                    new ShippingCountryConstraintRule(countryCodes: ['GB'])
                ],
                matchingType: RuleMatchingType::ALL
            )
        );

        $shippingMethod2 = new FlatRateShippingMethod(
            id: 'priority-shipping-gbp',
            name: 'Priority Shipping',
            price: new Price(2000, 'GBP'),
            ruleGroup: new RuleGroup(
                rules: [
                    new ShippingCountryConstraintRule(countryCodes: ['GB'])
                ],
                matchingType: RuleMatchingType::ALL
            )
        );

        $shippingMethod3 = new FlatRateShippingMethod(
            id: 'free-shipping-over-50-usd',
            name: 'Free Shipping',
            price: new Price(0, 'USD'),
            ruleGroup: new RuleGroup(
                rules: [
                    new BasketSubTotalConstraintRule(currency: 'USD', min: 5000),
                    new ShippingCountryConstraintRule(countryCodes: ['US'])
                ],
                matchingType: RuleMatchingType::ALL
            )
        );

        $this->shippingMethodRepository->allows()->get()->andReturns([$shippingMethod1, $shippingMethod2, $shippingMethod3]);

        $this->basket->subTotal = 6000;

        $rates = $this->shippingManager->getAvailableRates(AddressFactory::create(['countryCode' => 'GB']));

        $this->assertCount(2, $rates);
        $this->assertEquals(0, $rates[0]->price->amount);
        $this->assertEquals('free-shipping-over-50-gbp', $rates[0]->id);
        $this->assertEquals(2000, $rates[1]->price->amount);
        $this->assertEquals('priority-shipping-gbp', $rates[1]->id);
    }
}