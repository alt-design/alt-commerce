<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketCurrencyAction;
use AltDesign\AltCommerce\Actions\UpdateBasketQuantityAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Settings\Settings;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use PHPUnit\Framework\TestCase;
use Mockery;

class UpdateBasketCurrencyActionTest extends TestCase
{
    protected $basket;
    protected $settings;
    protected $basketRepository;
    protected $settingsRepository;
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
        $this->recalculateBasketAction->allows()->handle()->once();

        $this->settings = Mockery::mock(Settings::class);
        $this->settings->supportedCurrencies = ['GBP', 'USD', 'EUR'];$this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows()->get()->andReturn($this->settings);

        $this->action = new UpdateBasketCurrencyAction(
            basketRepository: $this->basketRepository,
            settingsRepository: $this->settingsRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_updating_currency_changes_basket_currency(): void
    {
        $this->action->handle('USD');
        $this->assertEquals('USD', $this->basket->currency);
    }

    public function test_items_get_removed_if_price_is_not_available(): void
    {

        $product1 = Mockery::mock(Product::class);
        $product1->allows()->prices()->andReturn(
            new PriceCollection($this->basketRepository, [
                new Price(100, 'GBP'),
                new Price(200, 'USD'),
            ])
        );

        $product2 = Mockery::mock(Product::class);
        $product2->allows()->prices()->andReturn(
            new PriceCollection($this->basketRepository, [
                new Price(100, 'GBP'),
                new Price(150, 'EUR'),
            ])
        );

        $lineItem1 = Mockery::mock(LineItem::class);
        $lineItem1->product = $product1;
        $lineItem2 = Mockery::mock(LineItem::class);
        $lineItem2->product = $product2;

        $this->basket->lineItems = [
            $lineItem1,
            $lineItem2,
        ];

        $this->action->handle('USD');
        $this->assertEquals([$lineItem1], $this->basket->lineItems);
    }

    public function test_exception_is_thrown_if_currency_is_not_supported(): void
    {
        $this->expectException(CurrencyNotSupportedException::class);
        $this->action->handle('XYZ');
    }

}