<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Contracts\VisitorLocator;
use AltDesign\AltCommerce\Support\Location;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

final class BasketFactoryTest extends TestCase
{

    use CommerceHelper;

    protected $basketFactory;
    protected $visitorLocator;

    protected function setUp(): void
    {
        $this->createSettings();

        $this->visitorLocator = Mockery::mock(VisitorLocator::class);
        $this->basketFactory = new BasketFactory(
            visitorLocator: $this->visitorLocator,
            settingsRepository: $this->settingsRepository
        );
    }

    public function test_create(): void
    {
        $this->visitorLocator->allows()->retrieve()->andReturns(new Location(countryCode: 'GB', currency: 'GBP'));
        $basket = $this->basketFactory->create();
        $this->assertEquals('GBP', $basket->currency);
        $this->assertEquals('GB', $basket->countryCode);

    }

    public function test_create_respects_default_currency(): void
    {
        $this->visitorLocator->allows()->retrieve()->andReturns(new Location(countryCode: 'GB', currency: 'GBP'));
        $this->settings->allows()->defaultCurrency()->andReturns('USD');
        $this->settings->allows()->supportedCurrencies()->andReturns(['USD']);

        $basket = $this->basketFactory->create();
        $this->assertEquals('USD', $basket->currency);

    }

    public function test_create_returns_defaults_when_location_cannot_be_retrieved(): void
    {
        $this->visitorLocator->allows()->retrieve()->andReturns(null);

        $this->settings->allows()->defaultCurrency()->andReturns('usd');
        $this->settings->allows()->defaultCountryCode()->andReturns('us');
        $this->settings->allows()->supportedCurrencies()->andReturns(['USD']);

        $basket = $this->basketFactory->create();
        $this->assertEquals('US', $basket->countryCode);
        $this->assertEquals('USD', $basket->currency);
    }

}