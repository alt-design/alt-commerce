<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AddToBasketAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\RecurrentBillingSchema;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class AddToBasketActionTest extends TestCase
{
    use CommerceHelper;

    protected $product;
    protected $productRepository;

    public function setUp(): void
    {
        $this->createBasket(currency: 'USD');

        $this->product = $this->createProduct(
            id: 'product-id',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(100, 'USD'),
                ])
            )
        );

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->productRepository->allows()->find('product-id')->andReturn($this->product);

    }

    public function test_adds_product_to_basket()
    {
        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle')->once();

        $action = new AddToBasketAction($this->basketRepository, $this->productRepository, $recalculateBasketActionMock);
        $action->handle('product-id', 2, ['color' => 'red']);

        $this->assertCount(1, $this->basket->lineItems);
        $this->assertInstanceOf(LineItem::class, $this->basket->lineItems[0]);
        $this->assertEquals('product-id', $this->basket->lineItems[0]->productId);
        $this->assertEquals('Test Product', $this->basket->lineItems[0]->productName);
        $this->assertEquals(2, $this->basket->lineItems[0]->quantity);
        $this->assertEquals(100, $this->basket->lineItems[0]->subTotal);
        $this->assertFalse( $this->basket->lineItems[0]->taxable);
    }

    public function test_updates_existing_product_quantity()
    {
        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle')->twice();

        $action = new AddToBasketAction($this->basketRepository, $this->productRepository, $recalculateBasketActionMock);
        $action->handle('product-id', 2);
        $action->handle('product-id', 3);

        $this->assertCount(1, $this->basket->lineItems);
        $this->assertEquals('product-id', $this->basket->lineItems[0]->productId);
        $this->assertEquals(5, $this->basket->lineItems[0]->quantity);
    }

    public function test_throws_exception_if_product_not_found()
    {
        $this->expectException(ProductNotFoundException::class);

        $this->productRepository->allows()->find('invalid-product-id')->andReturn(null);

        $action = new AddToBasketAction($this->basketRepository, $this->productRepository, Mockery::mock(RecalculateBasketAction::class));
        $action->handle('invalid-product-id', 2);
    }

    public function test_throws_exception_if_product_does_not_have_supported_currency()
    {
        $this->expectException(CurrencyNotSupportedException::class);
        $this->basket->currency = 'GBP';

        $action = new AddToBasketAction($this->basketRepository, $this->productRepository, Mockery::mock(RecalculateBasketAction::class));
        $action->handle('product-id', 1);
    }

    public function test_adds_product_with_recurrent_billing()
    {
        $this->product = $this->createProduct(
            id: 'product-recurrent-billing',
            priceSchema: new RecurrentBillingSchema(
                plans: [
                    new BillingPlan(
                        id: '1-month',
                        prices: new PriceCollection([
                            new Money(100, 'USD'),
                        ]),
                        billingInterval: new Duration(1, DurationUnit::MONTH)
                    )
                ]
            )
        );

        $this->productRepository->allows()->find('product-recurrent-billing')->andReturn($this->product);

        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle');

        $action = new AddToBasketAction($this->basketRepository, $this->productRepository, $recalculateBasketActionMock);
        $action->handle('product-recurrent-billing', 1, ['plan' => '1-month']);

        $this->assertCount(0, $this->basket->lineItems);
        $this->assertCount(1, $this->basket->billingItems);
        $this->assertEquals('product-recurrent-billing', $this->basket->billingItems[0]->productId);
        $this->assertEquals('1-month', $this->basket->billingItems[0]->planId);
        $this->assertEquals(100, $this->basket->billingItems[0]->amount);
        $this->assertEquals('1:month', (string)$this->basket->billingItems[0]->billingInterval);

    }

}