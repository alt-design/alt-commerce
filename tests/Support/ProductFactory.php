<?php

namespace AltDesign\AltCommerce\Tests\Support;

use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Support\PriceCollectionFactory;
use Faker\Factory;
use Mockery;
use Ramsey\Uuid\Uuid;

class ProductFactory
{
    public function __construct(protected  PriceCollectionFactory $priceCollectionFactory)
    {

    }

    public function create(array $args = []): Product
    {
        $faker = Factory::create();

        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn($args['id'] ?? Uuid::uuid4());
        $product->allows()->prices()->andReturn(
            $this->priceCollectionFactory->create(
                prices: new Price($args['price'] ?? $faker->numberBetween(10,20000), $args['currency'] ?? 'GBP')
            )
        );

        $product->allows()->taxable()->andReturn($args['taxable'] ?? false);

        $taxRule = new TaxRule(
            name: $args['taxName'] ?? 'default-tax-rate',
            rate: $args['taxRate'] ?? 20,
            countries: $args['taxCountries'] ?? ['GB'],
        );

        $product->allows()->taxRules()->andReturn($product->taxable() ? [$taxRule] : []);

        return $product;
    }
}