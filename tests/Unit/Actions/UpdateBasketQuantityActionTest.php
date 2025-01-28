<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketQuantityAction;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Exceptions\ProductNotFoundException;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class UpdateBasketQuantityActionTest extends TestCase
{
    use CommerceHelper;

    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new UpdateBasketQuantityAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_updating_quantity_with_existing_product()
    {
        $product = $this->createProduct(
            id: 'product-id',
            priceSchema: new FixedPriceSchema(
                prices:
                new PriceCollection([
                    new Money(200, 'GBP')
                ])
            )
        );
        $this->addLineItemToBasket($product, 2);

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