<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AddToBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Enum\ProductType;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class AddToBasketActionTest extends TestCase
{
    protected $basket;
    protected $basketRepository;
    protected $product;
    protected $productRepository;

    public function setUp(): void
    {
        $this->basket = Mockery::mock(Basket::class);
        $this->basket->lineItems = [];
        $this->basket->currency = 'USD';

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->product = Mockery::mock(Product::class);
        $this->product->allows()->id()->andReturn('product-id');
        $this->product->allows()->type()->andReturn(ProductType::OTHER);
        $this->product->allows()->taxable()->andReturn(false);
        $this->product->allows()->taxRules()->andReturn([]);
        $this->product->allows()->data()->andReturn([]);
        $this->product->allows()->name()->andReturn('Test Product');
        $this->product->allows()->prices()->andReturn(new PriceCollection([
            new Price(100, 'USD'),
        ]));

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
        $this->assertEquals(ProductType::OTHER, $this->basket->lineItems[0]->productType);
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

}