<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemSubtotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateProductCouponsDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTaxItems;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateTotals;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\ClearDiscounts;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\ValidateCoupons;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasketPipeline;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCouponPipeline;
use AltDesign\AltCommerce\Commerce\Pricing\FixedPriceSchema;
use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
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

        $validateCouponPipeline = Mockery::mock(ValidateCouponPipeline::class);
        $validateCouponPipeline->allows('handle');

        $this->pipeline = new RecalculateBasketPipeline(
            clearDiscounts: new ClearDiscounts(),
            validateCoupons: new ValidateCoupons(
                validateCouponPipeline: $validateCouponPipeline,
            ),
            calculateLineItemSubtotals: new CalculateLineItemSubtotals(),
            calculateProductCouponDiscounts: new CalculateProductCouponsDiscounts(),
            calculateLineItemTax: new CalculateLineItemTax(),
            calculateTaxItems: new CalculateTaxItems(),
            calculateTotals: new CalculateTotals(),
        );

    }

    public function test_basic_product_with_no_tax_rules(): void
    {

        $this->addLineItemToBasket($this->product1, 2);
        $this->addLineItemToBasket($this->product2, 5);

        $this->pipeline->handle($this->basket);

        $this->assertEquals(1450, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(1450, $this->basket->total);

        $this->assertEquals(100, $this->basket->lineItems[0]->amount);
        $this->assertEquals(200, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountTotal);

        $this->assertEquals(250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(1250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountTotal);

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

        $this->pipeline->handle($this->basket);

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
        $this->assertEquals(0, $this->basket->lineItems[1]->discountTotal);
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
                id: 'coupon-id-1',
                coupon: $this->createProductCoupon(
                    code: 'giveme5',
                    name: '£5 off everything',
                    discountAmount: 500,
                    eligibleProducts: ['product-1'],
                )
            )
        ];


        $this->pipeline->handle($this->basket);

        $this->assertEquals(59500, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(500, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(59500, $this->basket->total);

        $this->assertEquals(500, $this->basket->discountItems[0]->amount);
        $this->assertEquals('£5 off everything', $this->basket->discountItems[0]->name);

        $this->assertEquals(500, $this->basket->lineItems[0]->discountTotal);
        $this->assertEquals($this->basket->discountItems[0]->id, $this->basket->lineItems[0]->discounts[0]->discountItemId);

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
                id: 'coupon-id-1',
                coupon: $this->createProductCoupon(
                    code: '20OFF',
                    name: '20% off everything',
                    discountAmount: 20,
                    isPercentage: true,
                    eligibleProducts: ['product-1'],
                )
            )
        ];

        $this->pipeline->handle($this->basket);

        $this->assertEquals(48000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(12000, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(48000, $this->basket->total);

        $this->assertEquals(12000, $this->basket->discountItems[0]->amount);
        $this->assertEquals('20% off everything', $this->basket->discountItems[0]->name);

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
            new DiscountItem(
                id: 'discount-item-id',
                name: 'test',
                amount: 200,
                type: DiscountType::PRODUCT_COUPON,
                couponCode: 'test-code'
            )
        ];

        $this->pipeline->handle($this->basket);

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

        $coupon = $this->createProductCoupon(
            code: '20OFF',
            name: '20% off everything',
            discountAmount: 20,
            isPercentage: false,
        );

        $this->basket->discountItems = [
            new DiscountItem(
                id: 'discount-item-id',
                name: '20% off everything',
                amount: 20,
                type: DiscountType::PRODUCT_COUPON,
                couponCode: '20OFF',
            )
        ];

        $this->basket->coupons = [new CouponItem(id: 'coupon-id', coupon: $coupon)];

        $this->pipeline->handle($this->basket);

        $this->assertCount(1, $this->basket->discountItems);
    }

    public function test_tax_rules_are_included_with_no_country_filter()
    {
        $this->product1->allows()->taxRules()->andReturns([
            new TaxRule(name: 'VAT 20', rate: 20, countryFilter: [])
        ]);
        $this->product1->allows()->taxable()->andReturns(true);

        $this->addLineItemToBasket($this->product1, 1);

        $this->pipeline->handle($this->basket);

        $this->assertEquals(20, $this->basket->taxTotal);
    }

}