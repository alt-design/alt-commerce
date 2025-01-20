<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Coupon\FixedDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Coupon\PercentageDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Tax\TaxRule;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\Support\Price;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RecalculateBasketActionTest extends TestCase
{

    use CommerceHelper;

    protected $basket;
    protected $basketRepository;
    protected $productRepository;
    protected $action;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = new Basket(
            id: 'basket-id',
            currency: 'GBP',
            countryCode: 'GB',
        );

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturns($this->basket);

        $this->product1 = $this->createProductMock(
            id: 'product-1',
            name: 'Test Product 1',
            priceCollection: new PriceCollection([
                new Price(100, 'GBP'),
            ])
        );

        $this->product2 = $this->createProductMock(
            id: 'product-2',
            name: 'Test Product 2',
            priceCollection: new PriceCollection([
                new Price(250, 'GBP'),
            ])
        );

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->productRepository->allows()->find('product-1')->andReturns($this->product1);
        $this->productRepository->allows()->find('product-2')->andReturns($this->product2);

        $this->action = new RecalculateBasketAction(
            basketRepository: $this->basketRepository,
            productRepository: $this->productRepository
        );


    }

    public function test_basic_product_with_no_tax_rules(): void
    {

        $this->addProductToBasket($this->product1, 2);
        $this->addProductToBasket($this->product2, 5);

        $this->basketRepository->allows()->save($this->basket);

        $this->action->handle();

        $this->assertEquals(1450, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(1450, $this->basket->total);

        $this->assertEquals(100, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(200, $this->basket->lineItems[0]->amount);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountAmount);

        $this->assertEquals(250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(1250, $this->basket->lineItems[1]->amount);
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

        $this->addProductToBasket($this->product1, 2); //100 = 200  * 0.2 = 40
        $this->addProductToBasket($this->product2, 5); // 250 = 1250 * 0.1 = 125

        $this->basketRepository->allows()->save($this->basket);

        $this->action->handle();

        $this->assertEquals(1450, $this->basket->subTotal);
        $this->assertEquals(165, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(1615, $this->basket->total);

        $this->assertEquals(100, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(200, $this->basket->lineItems[0]->amount);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountAmount);

        $this->assertEquals(250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(1250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountAmount);

        $this->assertEquals(40, $this->basket->taxItems[0]->amount);
        $this->assertEquals(20, $this->basket->taxItems[0]->rate);

        $this->assertEquals(125, $this->basket->taxItems[1]->amount);
        $this->assertEquals(10, $this->basket->taxItems[1]->rate);
    }

    public function test_adding_fixed_discount_coupon_code(): void
    {
        $this->product1->allows()->prices()->andReturns(new PriceCollection([
            new Price(12000, 'GBP'),
        ]));

        $this->addProductToBasket($this->product1, 5);

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

        $this->action->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(-500, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(59500, $this->basket->total);

        $this->assertEquals(500, $this->basket->discountItems[0]->amount());
        $this->assertEquals('£5 off everything', $this->basket->discountItems[0]->name());

    }

    public function test_adding_percentage_discount_coupon_code(): void
    {
        $this->product1->allows()->prices()->andReturns(new PriceCollection([
            new Price(12000, 'GBP'),
        ]));

        $this->addProductToBasket($this->product1, 5);


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

        $this->action->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(-12000, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(48000, $this->basket->total);

        $this->assertEquals(12000, $this->basket->discountItems[0]->amount());
        $this->assertEquals('20% off everything', $this->basket->discountItems[0]->name());

    }


    public function test_removing_coupon_code(): void
    {

        $this->product1->allows()->prices()->andReturns(new PriceCollection([
            new Price(12000, 'GBP'),
        ]));

        $this->addProductToBasket($this->product1, 5);


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

        $this->action->handle();

        $this->assertEquals(60000, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(60000, $this->basket->total);

    }

    public function test_existing_coupon_codes_get_removed_before_recalculating(): void
    {

        $this->product1->allows()->prices()->andReturns(new PriceCollection([
            new Price(12000, 'GBP'),
        ]));

        $this->addProductToBasket($this->product1, 5);

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

        $this->action->handle();

        $this->assertCount(1, $this->basket->discountItems);
    }

    public function test_tax_rules_are_included_with_no_country_filter()
    {
        $this->product1->allows()->taxRules()->andReturns([
            new TaxRule(name: 'VAT 20', rate: 20, countryFilter: [])
        ]);
        $this->product1->allows()->taxable()->andReturns(true);

        $this->addProductToBasket($this->product1, 1);

        $this->basketRepository->allows()->save($this->basket);

        $this->action->handle();

        $this->assertEquals(20, $this->basket->taxTotal);



    }



}