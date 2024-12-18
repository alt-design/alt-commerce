<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Coupon\FixedDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Coupon\PercentageDiscountCoupon;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\Tests\Support\ProductFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class RecalculateBasketActionTest extends TestCase
{

    protected $basket;
    protected $basketRepository;
    protected $productRepository;
    protected $action;
    protected ProductFactory $productFactory;

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

        $this->productRepository = Mockery::mock(ProductRepository::class);

        $this->action = new RecalculateBasketAction(
            basketRepository: $this->basketRepository,
            productRepository: $this->productRepository
        );

        $this->productFactory = new ProductFactory();

    }

    public function test_basic_product_with_no_tax_rules(): void
    {
        $product1 = $this->productFactory->create([
            'id' => 'product-id-1',
            'price' => 5000,
        ]);

        $product2 = $this->productFactory->create([
            'id' => 'product-id-2',
            'price' => 250,
        ]);

        $this->basket->lineItems = [
            new LineItem(
                product: $product1,
                quantity: 2,
            ),
            new LineItem(
                product: $product2,
                quantity: 5,
            )
        ];

        $this->productRepository->allows()->find('product-id-1')->andReturns($product1);
        $this->productRepository->allows()->find('product-id-2')->andReturns($product2);
        $this->basketRepository->allows()->save($this->basket);

        $this->action->handle();

        $this->assertEquals(11250, $this->basket->subTotal);
        $this->assertEquals(0, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(11250, $this->basket->total)    ;

        $this->assertEquals(5000, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(10000, $this->basket->lineItems[0]->amount);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountAmount);

        $this->assertEquals(250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(1250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountAmount);

    }

    public function test_basic_product_with_tax_rules(): void
    {
        $product1 = $this->productFactory->create([
            'id' => 'product-id-1',
            'price' => 5000,
            'taxable' => true,
            'taxRate' => 20,
        ]);

        $product2 = $this->productFactory->create([
            'id' => 'product-id-2',
            'price' => 250,
            'taxable' => true,
            'taxRate' => 10,
        ]);

        $this->basket->lineItems = [
            new LineItem(
                product: $product1,
                quantity: 2,
            ),
            new LineItem(
                product: $product2,
                quantity: 5,
            )
        ];

        $this->productRepository->allows()->find('product-id-1')->andReturns($product1);
        $this->productRepository->allows()->find('product-id-2')->andReturns($product2);
        $this->basketRepository->allows()->save($this->basket);

        $this->action->handle();

        $this->assertEquals(11250, $this->basket->subTotal);
        $this->assertEquals(2125, $this->basket->taxTotal);
        $this->assertEquals(0, $this->basket->deliveryTotal);
        $this->assertEquals(0, $this->basket->discountTotal);
        $this->assertEquals(0, $this->basket->feeTotal);
        $this->assertEquals(13375, $this->basket->total);

        $this->assertEquals(5000, $this->basket->lineItems[0]->subTotal);
        $this->assertEquals(10000, $this->basket->lineItems[0]->amount);
        $this->assertEquals(0, $this->basket->lineItems[0]->discountAmount);

        $this->assertEquals(250, $this->basket->lineItems[1]->subTotal);
        $this->assertEquals(1250, $this->basket->lineItems[1]->amount);
        $this->assertEquals(0, $this->basket->lineItems[1]->discountAmount);

        $this->assertEquals(2000, $this->basket->taxItems[0]->amount);
        $this->assertEquals(20, $this->basket->taxItems[0]->rate);

        $this->assertEquals(125, $this->basket->taxItems[1]->amount);
        $this->assertEquals(10, $this->basket->taxItems[1]->rate);
    }

    public function test_adding_fixed_discount_coupon_code(): void
    {
        $product = $this->productFactory->create([
            'id' => 'product-id',
            'price' => 12000,
        ]);

        $this->basket->lineItems = [
            new LineItem(
                product: $product,
                quantity: 5,
            )
        ];

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

        $this->productRepository->allows()->find('product-id')->andReturns($product);
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
        $product = $this->productFactory->create([
            'id' => 'product-id',
            'price' => 12000,
        ]);

        $this->basket->lineItems = [
            new LineItem(
                product: $product,
                quantity: 5,
            )
        ];

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

        $this->productRepository->allows()->find('product-id')->andReturns($product);
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
        $product = $this->productFactory->create([
            'id' => 'product-id',
            'price' => 12000,
        ]);

        $coupon = Mockery::mock(Coupon::class);
        $coupon->allows()->code()->andReturn('test-code');

        $this->basket->lineItems = [
            new LineItem(
                product: $product,
                quantity: 5,
            )
        ];

        $this->basket->discountItems = [
            new CouponDiscountItem(
                name: 'test',
                amount: 200,
                coupon: $coupon
            )
        ];

        $this->productRepository->allows()->find('product-id')->andReturns($product);
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

        $coupon = Mockery::mock(Coupon::class);
        $coupon->allows()->code()->andReturn('20OFF');
        $coupon->allows()->discountType()->andReturn(DiscountType::FIXED);
        $coupon->allows()->discountAmount()->andReturn(20);
        $coupon->allows()->name()->andReturn('20% off everything');

        $product = $this->productFactory->create([
            'id' => 'product-id',
            'price' => 12000,
        ]);
        $this->productRepository->allows()->find('product-id')->andReturns($product);
        $this->basketRepository->allows()->save($this->basket);

        $this->basket->lineItems = [
            new LineItem(
                product: $product,
                quantity: 5,
            )
        ];

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
}