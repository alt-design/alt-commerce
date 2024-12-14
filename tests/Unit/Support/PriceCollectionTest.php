<?php

namespace AltDesign\AltCommerce\Tests\Unit\Support;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use Mockery;
use PHPUnit\Framework\TestCase;

class PriceCollectionTest extends TestCase
{
    protected $basket;
    protected $basketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = Mockery::mock(Basket::class);
        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);
    }

    public function test_default_returns_basket_currency(): void
    {

        $this->basket->currency = 'USD';
        $priceCollection = new PriceCollection(
            basketRepository: $this->basketRepository,
            prices: [
                new Price(1000, 'GBP'),
                new Price(2000, 'USD'),
                new Price(3000, 'EUR'),
            ]
        );

        $this->assertEquals('USD', $priceCollection->default()->currency);
        $this->assertEquals(2000, $priceCollection->default()->amount);
    }
}