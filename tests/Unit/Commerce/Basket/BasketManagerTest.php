<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;


class BasketManagerTest extends TestCase
{
    use CommerceHelper;

    protected $basketManager;

    public function setup(): void
    {
        $this->createBasket();

        $this->basketManager = new BasketManager($this->basketRepository);
    }

    public function test_find(): void
    {
        $product1 = $this->createProduct(
            id: 'test-1',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(5000, 'GBP')
                ])
            ));

        $this->addLineItemToBasket($product1, 1);

        $product2 = $this->createProduct(
            id: 'test-2',
            priceSchema: new RecurrentBillingSchema(
                plans: [
                    new BillingPlan(
                        id: '1-month',
                        prices: new PriceCollection([
                            new Money(5000, 'GBP')
                        ]),
                        billingInterval: new Duration(1, DurationUnit::MONTH)
                    )
                ]
            ));

        $this->addBillingItemToBasket($product2, '1-month');

        $this->assertEquals('test-1', $this->basketManager->find('test-1')->productId);
        $this->assertEquals('test-2', $this->basketManager->find('test-2')->productId);
        $this->assertNull($this->basketManager->find('test-3'));

    }
}