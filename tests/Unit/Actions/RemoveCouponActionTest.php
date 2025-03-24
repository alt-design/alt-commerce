<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\RemoveCouponAction;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class RemoveCouponActionTest extends TestCase
{
    use CommerceHelper;

    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        $this->createBasket();

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new RemoveCouponAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_removing_coupon_from_basket()
    {
        $coupon = $this->createProductCoupon(
            code: 'coupon-code',
            name: 'Coupon Name',
            discountAmount: 100,
        );

        $couponItem = Mockery::mock(CouponItem::class);
        $couponItem->coupon = $coupon;

        $this->basket->coupons = [
            $couponItem
        ];

        $this->basket->discountItems = [
            new DiscountItem(
                id: 'discount-item-id',
                name: $coupon->name(),
                amount: $coupon->discountAmount(),
                type: DiscountType::PRODUCT_COUPON,
                couponCode: $coupon->code(),
            )
        ];

        $this->recalculateBasketAction->allows('handle')->once();
        $this->basketRepository->allows('save')->once();

        $removedCoupon = $this->action->handle('coupon-code');

        $this->assertEmpty($this->basket->coupons);
        $this->assertSame($coupon, $removedCoupon);

    }

    public function test_removing_invalid_coupon_throws_exception()
    {
        $this->expectException(CouponNotValidException::class);
        $this->expectExceptionMessage(CouponNotValidReason::NOT_FOUND->value);

        $this->action->handle('invalid-coupon');
    }
}