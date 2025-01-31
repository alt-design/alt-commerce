<?php

namespace AltDesign\AltCommerce\Tests\Support;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\Settings;
use Mockery;
use Ramsey\Uuid\Uuid;

trait CommerceHelper
{
    protected $basket;
    protected $basketRepository;
    protected $settings;

    protected function createBasket(string $currency = 'GBP', string $id = 'test-basket', string $countryCode = 'GB')
    {
        $this->basket = Mockery::mock(Basket::class);
        $this->basket->id = $id;
        $this->basket->countryCode = $countryCode;
        $this->basket->currency = $currency;
        $this->basket->lineItems = [];
        $this->basket->billingItems = [];
        $this->basket->coupons = [];
        $this->basket->deliveryItems = [];
        $this->basket->feeItems = [];
        $this->basket->subTotal = 0;

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);
    }


    protected function createProduct($id, $name = null, ?PricingSchema $priceSchema = null)
    {
        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn($id)->byDefault();
        $product->allows()->taxable()->andReturn(false)->byDefault();
        $product->allows()->taxRules()->andReturn([])->byDefault();
        $product->allows()->data()->andReturn([])->byDefault();
        $product->allows()->name()->andReturn($name ?? 'Test Product')->byDefault();
        if ($priceSchema) {
            $product->allows()->price()->andReturn($priceSchema)->byDefault();
        }

        return $product;
    }

    protected function addLineItemToBasket($product, $quantity): LineItem
    {
        $lineItem = new LineItem(
            id: Uuid::uuid4(),
            productId: $product->id(),
            productName: $product->name(),
            taxable: $product->taxable(),
            taxRules: $product->taxRules(),
            productData: $product->data(),
            quantity: $quantity,
            subTotal: $product->price()->getAmount($this->basket->currency, ['quantity' => $quantity]),
        );
        $this->basket->lineItems[] = $lineItem;
        return $lineItem;
    }

    protected function addBillingItemToBasket($product, $planId): BillingItem
    {
        $billingPlan = $product->price()->getBillingPlan($this->basket->currency, ['plan' => $planId]);

        $billingItem = new BillingItem(
            id: Uuid::uuid4(),
            productId: $product->id(),
            billingPlanId: $billingPlan->id,
            productName: $product->name(),
            amount: $billingPlan->prices->getAmount($this->basket->currency, ['plan' => $planId]),
            billingInterval: $billingPlan->billingInterval,
        );
        $this->basket->billingItems[] = $billingItem;
        return $billingItem;
    }

    protected function createSettings(
        string $tradingName = 'AltCommerce',
        string $defaultCountryCode = 'USD',
        string $defaultCurrency = 'USD',
        array $supportedCurrencies = ['USD', 'GBP']
    ): void
    {
        $this->settings = Mockery::mock(Settings::class);
        $this->settings->allows()->tradingName()->andReturn($defaultCountryCode)->byDefault();
        $this->settings->allows()->defaultCountryCode()->andReturn($tradingName)->byDefault();
        $this->settings->allows()->defaultCurrency()->andReturn($defaultCurrency)->byDefault();
        $this->settings->allows()->supportedCurrencies()->andReturn($supportedCurrencies)->byDefault();
    }
}