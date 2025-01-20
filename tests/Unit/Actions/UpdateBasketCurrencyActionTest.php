<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketCurrencyAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Settings\Settings;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class UpdateBasketCurrencyActionTest extends TestCase
{
    use CommerceHelper;

    protected $basket;
    protected $settings;
    protected $basketRepository;
    protected $settingsRepository;
    protected $productRepository;
    protected $recalculateBasketAction;
    protected $action;
    protected $product1;

    protected function setUp(): void
    {

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->currency = 'GBP';
        $this->basket->lineItems = [];

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->settings = Mockery::mock(Settings::class);
        $this->settings->supportedCurrencies = ['GBP', 'USD', 'EUR'];$this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows()->get()->andReturn($this->settings);

        $this->product1 = $this->createProductMock(
            id: 'product-1',
            name: 'Test Product 1',
            priceCollection: new PriceCollection([
                new Price(100, 'GBP'),
                new Price(200, 'USD'),
            ])
        );

        $this->product2 = $this->createProductMock(
            id: 'product-2',
            name: 'Test Product 2',
            priceCollection: new PriceCollection([
                new Price(100, 'GBP'),
                new Price(150, 'EUR'),
            ])
        );

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->productRepository->allows()->find('product-1')->andReturn($this->product1);
        $this->productRepository->allows()->find('product-2')->andReturn($this->product2);

        $this->action = new UpdateBasketCurrencyAction(
            basketRepository: $this->basketRepository,
            settingsRepository: $this->settingsRepository,
            productRepository: $this->productRepository,
            recalculateBasketAction: $this->recalculateBasketAction
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
        $lineItem1 = $this->addProductToBasket($this->product1, 1);
        $this->addProductToBasket($this->product2, 1);

        $this->action->handle('USD');
        $this->assertEquals([$lineItem1], $this->basket->lineItems);
    }

    public function test_exception_is_thrown_if_currency_is_not_supported(): void
    {
        $this->expectException(CurrencyNotSupportedException::class);
        $this->action->handle('XYZ');
    }

}