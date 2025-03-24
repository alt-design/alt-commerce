<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\ApplyCouponAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCouponPipeline;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class ApplyCouponActionTest extends TestCase
{
    use CommerceHelper;

    protected $coupon;
    protected $couponRepository;
    protected $recalculateBasketAction;
    protected $couponValidator;
    protected $validateCouponPipeline;
    protected $action;


    protected function setUp(): void
    {
        $this->createBasket();

        $this->coupon = Mockery::mock(Coupon::class);

        $this->couponRepository = Mockery::mock(CouponRepository::class);

        $this->recalculateBasketAction= Mockery::mock(RecalculateBasketAction::class);

        $this->validateCouponPipeline = Mockery::mock(ValidateCouponPipeline::class);

        $this->action = new ApplyCouponAction(
            basketRepository: $this->basketRepository,
            couponRepository: $this->couponRepository,
            recalculateBasketAction: $this->recalculateBasketAction,
            validateCouponPipeline: $this->validateCouponPipeline,
        );
    }


    public function test_applying_valid_coupon(): void
    {
        $this->couponRepository->shouldReceive('find')->with('GBP', 'SAVE20')->once()->andReturn($this->coupon);
        $this->recalculateBasketAction->shouldReceive('handle')->once();
        $this->validateCouponPipeline->allows('handle');


        $this->coupon->allows()->code()->andReturn('SAVE20');
        $this->action->handle('SAVE20');

        $this->assertCount(1, $this->basket->coupons);
        $this->assertSame($this->coupon, $this->basket->coupons[0]->coupon);
    }


    public function test_invalid_coupon_throws_exception(): void
    {
        $this->expectException(CouponNotValidException::class);
        $this->expectExceptionMessage(CouponNotValidReason::NOT_FOUND->value);

        $this->validateCouponPipeline->allows('handle');

        $this->couponRepository->shouldReceive('find')->with('GBP', 'NOLONGER')->once()->andReturn(null);

        $this->action->handle('NOLONGER');
    }

    public function test_coupon_throw_exception_when_failed_validation(): void
    {
        $this->expectException(CouponNotValidException::class);

        $this->couponRepository->shouldReceive('find')->with('GBP', 'SAVE20')->once()->andReturn($this->coupon);
        $this->validateCouponPipeline->shouldReceive('handle')->once()->andThrow(new CouponNotValidException(
            reason: CouponNotValidReason::NOT_ELIGIBLE,
        ));

        $this->action->handle('SAVE20');
    }

}