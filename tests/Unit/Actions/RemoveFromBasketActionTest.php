<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RemoveFromBasketAction;
use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RemoveFromBasketActionTest extends TestCase
{

    use CommerceHelper;

    protected $action;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->action = new RemoveFromBasketAction(
            context: $this->basketContext,
        );
    }

    public function test_remove_product_from_basket()
    {
        $product1 = $this->createProduct(
            id: 'product-1',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(200, 'GBP')
                ])
            )
        );

        $product2 = $this->createProduct(
            id: 'product-2',
            priceSchema: new RecurrentBillingSchema(
                plans: [
                    new BillingPlan(
                        id: '1-month',
                        name: 'monthly plan',
                        prices: new PriceCollection([
                            new Money(200, 'GBP')
                        ]),
                        billingInterval: new Duration(1, DurationUnit::MONTH),
                        createdAt: new \DateTimeImmutable(),
                        updatedAt: new \DateTimeImmutable(),
                    )
                ]
            )
        );

        $lineItem = $this->addLineItemToBasket($product1, 2);
        $billingItem = $this->addBillingItemToBasket($product2, '1-month');

        $this->action->handle($lineItem->id, $billingItem->id);

        $this->assertEmpty($this->basket->lineItems);
        $this->assertEmpty($this->basket->billingItems);

    }


}