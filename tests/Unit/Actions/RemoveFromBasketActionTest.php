<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\RemoveFromBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use PHPUnit\Framework\TestCase;

class RemoveFromBasketActionTest extends TestCase
{

    use CommerceHelper;

    protected $basket;
    protected $basketRepository;
    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->lineItems = [];
        $this->basket->currency = 'GBP';

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
        $product = $this->createProductMock(
            id: 'product-id',
            priceCollection: new PriceCollection([
                new Price(200, 'GBP')
            ])
        );

        $this->addProductToBasket($product, 2);

        $this->recalculateBasketAction->allows('handle')->once();
        $this->basketRepository->allows('save')->once();

        $this->action->handle('product-id');

        $this->assertEmpty($this->basket->lineItems);

    }
}