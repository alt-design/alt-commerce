<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Actions\RemoveCouponAction;
use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\CouponItem;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Exceptions\CouponNotFoundException;
use Mockery;
use PHPUnit\Framework\TestCase;

class RemoveCouponActionTest extends TestCase
{
    protected $basket;
    protected $basketRepository;
    protected $recalculateBasketAction;
    protected $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basket = Mockery::mock(Basket::class);
        $this->basket->coupons = [];

        $this->basketRepository = Mockery::mock(BasketRepository::class);
        $this->basketRepository->allows()->get()->andReturn($this->basket);

        $this->recalculateBasketAction = Mockery::mock(RecalculateBasketAction::class);

        $this->action = new RemoveCouponAction(
            basketRepository: $this->basketRepository,
            recalculateBasketAction: $this->recalculateBasketAction
        );
    }

    public function test_removing_coupon_from_basket()
    {
        $coupon = Mockery::mock(Coupon::class);
        $coupon->allows()->code()->andReturn('coupon-code');

        $couponItem = Mockery::mock(CouponItem::class);
        $couponItem->coupon = $coupon;

        $this->basket->coupons = [
            $couponItem
        ];

        $this->recalculateBasketAction->allows('handle')->once();
        $this->basketRepository->allows('save')->once();

        $removedCoupon = $this->action->handle('coupon-code');

        $this->assertEmpty($this->basket->coupons);
        $this->assertSame($coupon, $removedCoupon);

    }

    public function test_removing_invalid_coupon_throws_exception()
    {
        $this->expectException(CouponNotFoundException::class);

        $this->action->handle('invalid-coupon');
    }
}