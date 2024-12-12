<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\RemoveFromBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use Mockery;
use PHPUnit\Framework\TestCase;

class RemoveFromBasketActionTest extends TestCase
{

    protected $basket;
    protected $basketRepository;
    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->lineItems = [];

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new RemoveFromBasketAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_remove_product_from_basket()
    {
        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn('product-id');

        $lineItem = Mockery::mock(LineItem::class);
        $lineItem->product = $product;

        $this->basket->lineItems = [
            $lineItem
        ];

        $this->recalculateBasketAction->allows('handle')->once();
        $this->basketRepository->allows('save')->once();

        $this->action->handle('product-id');

        $this->assertEmpty($this->basket->lineItems);

    }
}