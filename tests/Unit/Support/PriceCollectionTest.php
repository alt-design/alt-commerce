<?php

namespace AltDesign\AltCommerce\Tests\Unit\Support;

use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class PriceCollectionTest extends TestCase
{

    public function test_collection(): void
    {
        $priceCollection = new PriceCollection(
            prices: [
                new Price(1000, 'GBP'),
                new Price(2000, 'USD'),
                new Price(3000, 'EUR'),
            ]
        );

        $this->assertTrue($priceCollection->supports('GbP'));
        $this->assertTrue($priceCollection->supports('USD'));
        $this->assertTrue($priceCollection->supports('EUR'));
        $this->assertFalse($priceCollection->supports('JPY'));

        $this->assertEquals(1000, $priceCollection->currency('GBp'));
        $this->assertEquals(2000, $priceCollection->currency('usd'));
        $this->assertEquals(3000, $priceCollection->currency('Eur'));

    }


}