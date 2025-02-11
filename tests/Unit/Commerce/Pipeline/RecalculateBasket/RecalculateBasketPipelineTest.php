<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Coupon\FixedDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Coupon\PercentageDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateDiscountItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemSubtotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTaxItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Mockery;

class RecalculateBasketPipelineTest extends TestCase
{
    use CommerceHelper;

    protected $productRepository;
    protected $pipeline;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->product1 = $this->createProduct(
            id: 'product-1',
            name: 'Test Product 1',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(100, 'GBP'),
                ])
            )
        );

        $this->product2 = $this->createProduct(
            id: 'product-2',
            name: 'Test Product 2',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(250, 'GBP'),
                ])
            )
        );

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->productRepository->allows()->find('product-1')->andReturns($this->product1);
        $this->productRepository->allows()->find('product-2')->andReturns($this->product2);

        $this->pipeline = new RecalculateBasketPipeline(
            basketRepository: $this->basketRepository,
            calculateLineItemSubtotals: new CalculateLineItemSubtotals(),
            calculateDiscountItems: new CalculateDiscountItems(),
            calculateLineItemDiscounts: new CalculateLineItemDiscounts(),
            calculateLineItemTax: new CalculateLineItemTax(),
            calculateTaxItems: new CalculateTaxItems(),
            calculateTotals: new CalculateTotals(),
        );

    }

    public function test_basic_product_with_no_tax_rules(): void
    {

        $this->addLineItemToBasket($this->product1, 2);
        $this->addLineItemToBasket($this->product2, 5);

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(1450, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(1450, $this->basket->total);

        $this->assertEquals(100, $this->basket->lineItems[0]->amount);
        $this->assertEquals(200, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountAmount);

        $this->assertEquals(250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(1250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountAmount);

    }

    public function test_basic_product_with_tax_rules(): void
    {
        $this->product1->allows()->taxable()->andReturn(true);
        $this->product1->allows()->taxRules()->andReturn([
            new TaxRule(
                name: 'default-tax-rate',
                rate: 20,
                countryFilter: ['GB'],
            )
        ]);

        $this->product2->allows()->taxable()->andReturn(true);
        $this->product2->allows()->taxRules()->andReturn([
            new TaxRule(
                name: 'default-tax-rate',
                rate: 10,
                countryFilter: ['GB'],
            )
        ]);

        $this->addLineItemToBasket($this->product1, 2); //100 = 200  * 0.2 = 40
        $this->addLineItemToBasket($this->product2, 5); // 250 = 1250 * 0.1 = 125

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(1450, $this->basket->subTotal);
        $this->assertEquals(165, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(1615, $this->basket->total);

        $this->assertEquals(100, $this->basket->lineItems[0]->amount);
        $this->assertEquals(200, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountTotal);
        $this->assertEquals(20, $this->basket->lineItems[0]->taxRate);
        $this->assertEquals(40, $this->basket->lineItems[0]->taxTotal);

        $this->assertEquals(250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(1250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountAmount);
        $this->assertEquals(10, $this->basket->lineItems[1]->taxRate);
        $this->assertEquals(125, $this->basket->lineItems[1]->taxTotal);

        $this->assertEquals(40, $this->basket->taxItems[0]->amount);
        $this->assertEquals(20, $this->basket->taxItems[0]->rate);

        $this->assertEquals(125, $this->basket->taxItems[1]->amount);
        $this->assertEquals(10, $this->basket->taxItems[1]->rate);
    }

    public function test_adding_fixed_discount_coupon_code(): void
    {

        $this->product1->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(12000, 'GBP'),
                ])
            )
        );

        $this->addLineItemToBasket($this->product1, 5);

        $this->basket->coupons = [
            new CouponItem(
                coupon: new FixedDiscountCoupon(
                    name: '£5 off everything',
                    code: 'giveme5',
                    currency: 'GBP',
                    discountAmount: 500,
                    ruleGroup: new RuleGroup(rules: [])
                )
            )
        ];

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(500, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(59500, $this->basket->total);

        $this->assertEquals(500, $this->basket->discountItems[0]->amount());
        $this->assertEquals('£5 off everything', $this->basket->discountItems[0]->name());

        $this->assertEquals(500, $this->basket->lineItems[0]->discountTotal);

    }

    public function test_adding_percentage_discount_coupon_code(): void
    {

        $this->product1->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(12000, 'GBP'),
                ])
            )
        );

        $this->addLineItemToBasket($this->product1, 5);

        $this->basket->coupons = [
            new CouponItem(
                coupon: new PercentageDiscountCoupon(
                    name: '20% off everything',
                    code: '20OFF',
                    currency: 'GBP',
                    discountAmount: 20,
                    ruleGroup: new RuleGroup(rules: [])
                )
            )
        ];

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(12000, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(48000, $this->basket->total);

        $this->assertEquals(12000, $this->basket->discountItems[0]->amount());
        $this->assertEquals('20% off everything', $this->basket->discountItems[0]->name());

        $this->assertEquals(12000, $this->basket->lineItems[0]->discountTotal);

    }

    public function test_removing_coupon_code(): void
    {
        $this->product1->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(12000, 'GBP'),
                ])
            )
        );

        $this->addLineItemToBasket($this->product1, 5);

        $coupon = Mockery::mock(Coupon::class);
        $coupon->allows()->code()->andReturn('test-code');

        $this->basket->discountItems = [
            new CouponDiscountItem(
                name: 'test',
                amount: 200,
                coupon: $coupon
            )
        ];

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(60000, $this->basket->total);

    }

    public function test_existing_coupon_codes_get_removed_before_recalculating(): void
    {
        $this->addLineItemToBasket($this->product1, 5);
        $this->basketRepository->allows()->save($this->basket);

        $coupon = Mockery::mock(Coupon::class);
        $coupon->allows()->code()->andReturn('20OFF');
        $coupon->allows()->discountType()->andReturn(DiscountType::FIXED);
        $coupon->allows()->discountAmount()->andReturn(20);
        $coupon->allows()->name()->andReturn('20% off everything');

        $this->basket->discountItems = [
            new CouponDiscountItem(
                name: '20% off everything',
                amount: 20,
                coupon: $coupon
            )
        ];

        $this->basket->coupons = [new CouponItem(coupon: $coupon)];

        $this->pipeline->handle();

        $this->assertCount(1, $this->basket->discountItems);
    }

    public function test_tax_rules_are_included_with_no_country_filter()
    {
        $this->product1->allows()->taxRules()->andReturns([
            new TaxRule(name: 'VAT 20', rate: 20, countryFilter: [])
        ]);
        $this->product1->allows()->taxable()->andReturns(true);

        $this->addLineItemToBasket($this->product1, 1);

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(20, $this->basket->taxTotal);
    }

    public function test_fixed_coupon_codes_get_proportionately_applied_across_eligible_line_items()
    {
        $this->product1->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(5000, 'GBP'),
                ])
            )
        );

        $this->product2->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(6000, 'GBP'),
                ])
            )
        );

        $product3 = $this->createProduct(
            id: 'product-3',
            name: 'Test Product 3',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(7000, 'GBP'),
                ])
            )
        );

        $this->productRepository->allows()->find('product-3')->andReturn($product3);

        $this->addLineItemToBasket($this->product1, 2);
        $this->addLineItemToBasket($this->product2, 1);
        $this->addLineItemToBasket($product3, 3);

        $this->basket->coupons = [
            new CouponItem(
                coupon: new FixedDiscountCoupon(
                    name: '£40 off',
                    code: '4OFF',
                    currency: 'GBP',
                    discountAmount: 4000,
                    ruleGroup: new RuleGroup(rules: [])
                )
            )
        ];

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(1081, $this->basket->lineItems[0]->discountTotal);
        $this->assertEquals(648, $this->basket->lineItems[1]->discountTotal);
        $this->assertEquals(2271, $this->basket->lineItems[2]->discountTotal);

    }

    public function test_percentage_coupon_codes_get_proportionately_applied_across_eligible_line_items()
    {
        $this->product1->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(5000, 'GBP'),
                ])
            )
        );

        $this->product2->allows()->price()->andReturns(
            new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(6000, 'GBP'),
                ])
            )
        );

        $product3 = $this->createProduct(
            id: 'product-3',
            name: 'Test Product 3',
            priceSchema: new FixedPriceSchema(
                prices: new PriceCollection([
                    new Money(7000, 'GBP'),
                ])
            )
        );

        $this->productRepository->allows()->find('product-3')->andReturn($product3);

        $this->addLineItemToBasket($this->product1, 2); // 10000
        $this->addLineItemToBasket($this->product2, 1); // 6000
        $this->addLineItemToBasket($product3, 3); // 21000

        $this->basket->coupons = [
            new CouponItem(
                coupon: new PercentageDiscountCoupon(
                    name: '10% off',
                    code: '10OFF',
                    currency: 'GBP',
                    discountAmount: 10,
                    ruleGroup: new RuleGroup(rules: [])
                )
            )
        ];

        $this->basketRepository->allows()->save($this->basket);

        $this->pipeline->handle();

        $this->assertEquals(1000, $this->basket->lineItems[0]->discountTotal);
        $this->assertEquals(600, $this->basket->lineItems[1]->discountTotal);
        $this->assertEquals(2100, $this->basket->lineItems[2]->discountTotal);

    }
}