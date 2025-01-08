<?php

namespace AltDesign\AltCommerce\Tests\Support;

use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Enum\ProductType;
use AltDesign\AltCommerce\Support\PriceCollection;
use Mockery;

trait CommerceHelper
{
    protected function createProductMock($id, $name = null, PriceCollection $priceCollection = null)
    {
        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn($id)->byDefault();
        $product->allows()->type()->andReturn(ProductType::OTHER)->byDefault();
        $product->allows()->taxable()->andReturn(false)->byDefault();
        $product->allows()->taxRules()->andReturn([])->byDefault();
        $product->allows()->data()->andReturn([])->byDefault();
        $product->allows()->name()->andReturn($name ?? 'Test Product')->byDefault();
        $product->allows()->prices()->andReturn($priceCollection ?? new PriceCollection())->byDefault();

        return $product;
    }

    protected function addProductToBasket($product, $quantity): LineItem
    {
        $lineItem = new LineItem(
            productId: $product->id(),
            productName: $product->name(),
            productType: $product->type(),
            taxable: $product->taxable(),
            taxRules: $product->taxRules(),
            productData: $product->data(),
            quantity: $quantity,
            subTotal: $product->prices()->currency($this->basket->currency),
        );
        $this->basket->lineItems[] = $lineItem;
        return $lineItem;
    }
}