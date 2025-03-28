<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
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

    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new RemoveFromBasketAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
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

        $this->addLineItemToBasket($product1, 2);
        $this->addBillingItemToBasket($product2, '1-month');

        $this->recalculateBasketAction->allows('handle')->once();
        $this->basketRepository->allows('save')->once();

        $this->action->handle('product-1', 'product-2');

        $this->assertEmpty($this->basket->lineItems);
        $this->assertEmpty($this->basket->billingItems);

    }


}