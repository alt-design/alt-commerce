<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketCurrencyAction;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class UpdateBasketCurrencyActionTest extends TestCase
{
    use CommerceHelper;

    protected $productRepository;
    protected $recalculateBasketAction;
    protected $action;
    protected $product1;

    protected function setUp(): void
    {
        $this->createBasket();
        $this->createSettings(supportedCurrencies: ['GBP','USD','EUR']);

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->product1 = $this->createProduct(
            id: 'product-1',
            name: 'Test Product 1',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(100, 'GBP'),
                    new Money(200, 'USD'),
                ])
            )
        );

        $this->product2 = $this->createProduct(
            id: 'product-2',
            name: 'Test Product 2',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(100, 'GBP'),
                    new Money(150, 'EUR'),
                ])
            )
        );

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->productRepository->allows()->find('product-1')->andReturn($this->product1);
        $this->productRepository->allows()->find('product-2')->andReturn($this->product2);

        $this->action = new UpdateBasketCurrencyAction(
            basketRepository: $this->basketRepository,
            productRepository: $this->productRepository,
            recalculateBasketAction: $this->recalculateBasketAction,
            settings: $this->settings
        );
    }

    public function test_updating_currency_changes_basket_currency(): void
    {
        $this->recalculateBasketAction->allows()->handle()->once();
        $this->action->handle('USD');
        $this->assertEquals('USD', $this->basket->currency);
    }

    public function test_items_get_removed_if_price_is_not_available(): void
    {
        $this->recalculateBasketAction->allows()->handle()->once();
        $lineItem1 = $this->addLineItemToBasket($this->product1, 1);
        $this->addLineItemToBasket($this->product2, 1);

        $this->action->handle('USD');
        $this->assertEquals([$lineItem1], $this->basket->lineItems);
    }

    public function test_exception_is_thrown_if_currency_is_not_supported(): void
    {
        $this->expectException(CurrencyNotSupportedException::class);
        $this->action->handle('XYZ');
    }

}