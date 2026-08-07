<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Basket\BasketBroker;
use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
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
use Mockery;


class BasketManagerTest extends TestCase
{
    use CommerceHelper;

    protected $basketManager;

    public function setup(): void
    {
        $this->createBasket();

        // A real BasketContext (over a driver mock) so the delegated find() runs
        // against the basket rather than a stub.
        $driver = Mockery::mock(\AltDesign\AltCommerce\Contracts\BasketDriver::class);
        $driver->allows()->get()->andReturn($this->basket);
        $context = new BasketContext(
            resolver: Mockery::mock(\AltDesign\AltCommerce\Contracts\Resolver::class),
            driver: $driver,
            context: 'default',
        );

        $broker = Mockery::mock(BasketBroker::class);
        $broker->allows()->context('default')->andReturn($context);

        $this->basketManager = new BasketManager($broker);
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
                        name: 'monthly plan',
                        prices: new PriceCollection([
                            new Money(5000, 'GBP')
                        ]),
                        billingInterval: new Duration(1, DurationUnit::MONTH),
                        createdAt: new \DateTimeImmutable(),
                        updatedAt: new \DateTimeImmutable(),
                    )
                ]
            ));

        $this->addBillingItemToBasket($product2, '1-month');

        $this->assertEquals('test-1', $this->basketManager->find('test-1')->productId);
        $this->assertEquals('test-2', $this->basketManager->find('test-2')->productId);
        $this->assertNull($this->basketManager->find('test-3'));

    }
}