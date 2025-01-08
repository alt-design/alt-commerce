<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketQuantityAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateBasketQuantityActionTest extends TestCase
{

    use CommerceHelper;

    protected $basket;
    protected $basketRepository;
    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->currency = 'GBP';
        $this->basket->lineItems = [];

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new UpdateBasketQuantityAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_updating_quantity_with_existing_product()
    {
        $product = $this->createProductMock(
            id: 'product-id',
            priceCollection: new PriceCollection([
                new Price(200, 'GBP')
            ])
        );
        $this->addProductToBasket($product, 2);

        $this->basketRepository->allows()->save($this->basket);
        $this->recalculateBasketAction->allows('handle')->once();

        $this->action->handle('product-id', 3);

        $this->assertEquals('product-id', $this->basket->lineItems[0]->productId);
        $this->assertEquals(3, $this->basket->lineItems[0]->quantity);
    }

    public function test_updating_quantity_with_non_existing_product_throws_exception()
    {
        $this->expectException(ProductNotFoundException::class);

        $this->action->handle('invalid-product-id', 2);
    }
}