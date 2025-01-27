<?php

namespace AltDesign\AltCommerce\Tests\Unit\Support;

use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class PriceCollectionTest extends TestCase
{

    public function test_collection(): void
    {

        $priceCollection = new PriceCollection(
            prices: [
                new Money(1000, 'GBP'),
                new Money(2000, 'USD'),
                new Money(3000, 'EUR'),
            ]
        );

        $this->assertTrue($priceCollection->isCurrencySupported('GbP'));
        $this->assertTrue($priceCollection->isCurrencySupported('USD'));
        $this->assertTrue($priceCollection->isCurrencySupported('EUR'));
        $this->assertFalse($priceCollection->isCurrencySupported('JPY'));

        $this->assertEquals(1000, $priceCollection->getAmount('GBp'));
        $this->assertEquals(2000, $priceCollection->getAmount('usd'));
        $this->assertEquals(3000, $priceCollection->getAmount('Eur'));

    }


}