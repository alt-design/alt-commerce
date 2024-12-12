<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\AddToBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use PHPUnit\Framework\TestCase;
use Mockery;

class AddToBasketActionTest extends TestCase
{

    public function test_adds_product_to_basket()
    {
        $basketMock = Mockery::mock(Basket::class);
        $basketMock->lineItems = [];

        $basketRepositoryMock = Mockery::mock(BasketRepository::class);
        $basketRepositoryMock->allows()->get()->andReturn($basketMock);
        $basketRepositoryMock->allows()->save($basketMock);

        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn('product-id');

        $productRepositoryMock = Mockery::mock(ProductRepository::class);
        $productRepositoryMock->allows()->find('product-id')->andReturn($product);

        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle')->once();

        $action = new AddToBasketAction($basketRepositoryMock, $productRepositoryMock, $recalculateBasketActionMock);
        $action->handle('product-id', 2, ['color' => 'red']);

        $this->assertCount(1, $basketMock->lineItems);
        $this->assertInstanceOf(LineItem::class, $basketMock->lineItems[0]);
        $this->assertEquals('product-id', $basketMock->lineItems[0]->product->id());
        $this->assertEquals(2, $basketMock->lineItems[0]->quantity);
    }

    public function test_updates_existing_product_quantity()
    {
        $basketMock = Mockery::mock(Basket::class);
        $basketMock->lineItems = [];

        $basketRepositoryMock = Mockery::mock(BasketRepository::class);
        $basketRepositoryMock->allows()->get()->andReturn($basketMock);
        $basketRepositoryMock->allows()->save($basketMock);

        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn('product-id');

        $productRepositoryMock = Mockery::mock(ProductRepository::class);
        $productRepositoryMock->allows()->find('product-id')->andReturn($product);

        $recalculateBasketActionMock = Mockery::mock(RecalculateBasketAction::class);
        $recalculateBasketActionMock->allows('handle')->once();

        $action = new AddToBasketAction($basketRepositoryMock, $productRepositoryMock, $recalculateBasketActionMock);
        $action->handle('product-id', 2);
        $action->handle('product-id', 3);

        $this->assertCount(1, $basketMock->lineItems);
        $this->assertEquals('product-id', $basketMock->lineItems[0]->product->id());
        $this->assertEquals(5, $basketMock->lineItems[0]->quantity);
    }

    public function test_throws_exception_if_product_not_found()
    {
        $this->expectException(ProductNotFoundException::class);

        $basketMock = Mockery::mock(Basket::class);
        $basketMock->lineItems = [];

        $basketRepositoryMock = Mockery::mock(BasketRepository::class);
        $basketRepositoryMock->allows()->get()->andReturn($basketMock);

        $productRepositoryMock = Mockery::mock(ProductRepository::class);
        $productRepositoryMock->allows()->find('invalid-product-id')->andReturn(null);

        $action = new AddToBasketAction($basketRepositoryMock, $productRepositoryMock, Mockery::mock(RecalculateBasketAction::class));
        $action->handle('invalid-product-id', 2);
    }

}