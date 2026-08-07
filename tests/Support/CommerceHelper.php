<?php

namespace AltDesign\AltCommerce\Tests\Support;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductCoupon;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Enum\StockPolicy;
use Mockery;
use Ramsey\Uuid\Uuid;

trait CommerceHelper
{
    protected $basket;
    protected $basketRepository;
    protected $basketContext;
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
        $this->basket->discountItems = [];

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->basketContext = $this->createBasketContext();
    }

    /**
     * Actions now take a BasketContext rather than a BasketRepository. The mock
     * exposes the current basket and no-ops the side-effect methods (save,
     * clear, recalculateBasket, …) so individual actions can be unit tested in
     * isolation. Override with expects() in a test when the call matters.
     */
    protected function createBasketContext(): BasketContext
    {
        $context = Mockery::mock(BasketContext::class);
        $context->allows()->current()->andReturn($this->basket)->byDefault();
        $context->allows('save')->byDefault();
        $context->allows('clear')->byDefault();
        $context->allows('recalculateBasket')->byDefault();
        $context->allows('updateBasketQuantity')->byDefault();
        $context->allows('removeFromBasket')->byDefault();

        return $context;
    }

    protected function createProduct($id, $name = null, ?PricingSchema $priceSchema = null, StockPolicy $stockPolicy = StockPolicy::UNTRACKED)
    {
        $product = Mockery::mock(Product::class);
        $product->allows()->id()->andReturn($id)->byDefault();
        $product->allows()->taxable()->andReturn(false)->byDefault();
        $product->allows()->taxRules()->andReturn([])->byDefault();
        $product->allows()->data()->andReturn([])->byDefault();
        $product->allows()->name()->andReturn($name ?? 'Test Product')->byDefault();
        $product->allows()->stockPolicy()->andReturn($stockPolicy)->byDefault();
        if ($priceSchema) {
            $product->allows()->price()->andReturn($priceSchema)->byDefault();
        }

        return $product;
    }

    protected function createProductCoupon(string $code, string $name, int $discountAmount, bool $isPercentage = false, string $currency = 'GBP', array $eligibleProducts = [])
    {
        $coupon = Mockery::mock(ProductCoupon::class);
        $coupon->allows()->code()->andReturn($code);
        $coupon->allows()->name()->andReturn($name);
        $coupon->allows()->discountAmount()->andReturn($discountAmount);
        $coupon->allows()->isPercentage()->andReturn($isPercentage);
        $coupon->allows()->currency()->andReturn($currency);
        $coupon->allows('isProductEligible')
            ->andReturnUsing(
                fn($productId) => in_array($productId, $eligibleProducts)
            );

        return $coupon;
    }

    protected function addLineItemToBasket($product, $quantity): LineItem
    {
        $lineItem = new LineItem(
            id: Uuid::uuid4(),
            productId: $product->id(),
            productName: $product->name(),
            amount: $product->price()->getAmount($this->basket->currency, ['quantity' => $quantity]),
            quantity: $quantity,
            taxable: $product->taxable(),
            taxRules: $product->taxRules(),
            productData: $product->data(),
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
        $this->settings->allows()->pricesInclusive()->andReturn(false)->byDefault();
    }
}