<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\UpdateBasketCountryAction;
use AltDesign\AltCommerce\Actions\UpdateBasketCurrencyAction;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class UpdateBasketCountryActionTest extends TestCase
{
    use CommerceHelper;

    protected function setUp(): void
    {
        $this->createBasket(countryCode: 'GB');
        $this->action = new UpdateBasketCountryAction(
            basketRepository: $this->basketRepository,
        );
    }

    public function test_updating_country(): void
    {
        $this->action->handle('US');
        $this->assertEquals('US', $this->basket->countryCode);
    }

}