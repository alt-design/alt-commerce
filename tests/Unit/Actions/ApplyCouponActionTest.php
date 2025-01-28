<?php

namespace AltDesign\AltCommerce\Tests\Unit\Actions;

use AltDesign\AltCommerce\Actions\ApplyCouponAction;
use AltDesign\AltCommerce\Actions\RecalculateBasketAction;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Exceptions\CouponNotFoundException;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\RuleEngine\EvaluationResult;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\RuleEngine\RuleManager;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use Mockery;
use AltDesign\AltCommerce\Tests\Unit\TestCase;

class ApplyCouponActionTest extends TestCase
{
    use CommerceHelper;

    protected $coupon;
    protected $couponRepository;
    protected $recalculateBasketAction;
    protected $ruleManager;
    protected $action;

    protected function setUp(): void
    {
        $this->createBasket();
        $this->basketRepository->shouldReceive('save')->with($this->basket);

        $this->coupon = Mockery::mock(Coupon::class);
        $this->coupon->allows()->ruleGroup()->andReturn(new RuleGroup(rules: []));

        $this->couponRepository = Mockery::mock(CouponRepository::class);


        $this->recalculateBasketAction= Mockery::mock(RecalculateBasketAction::class);

        $this->ruleManager = Mockery::mock(RuleManager::class);

        $this->action = new ApplyCouponAction(
            basketRepository: $this->basketRepository,
            couponRepository: $this->couponRepository,
            recalculateBasketAction: $this->recalculateBasketAction,
            ruleManager: $this->ruleManager
        );
    }


    public function test_applying_valid_coupon(): void
    {
        $this->couponRepository->shouldReceive('find')->with('GBP', 'SAVE20')->once()->andReturn($this->coupon);
        $this->recalculateBasketAction->shouldReceive('handle')->once();
        $this->ruleManager->shouldReceive('evaluate')->once()->andReturn(new EvaluationResult(true));

        $this->coupon->allows()->code()->andReturn('SAVE20');
        $this->action->handle('SAVE20');

        $this->assertCount(1, $this->basket->coupons);
        $this->assertSame($this->coupon, $this->basket->coupons[0]->coupon);
    }


    public function test_invalid_coupon_throws_exception(): void
    {
        $this->expectException(CouponNotFoundException::class);

        $this->couponRepository->shouldReceive('find')->with('GBP', 'NOLONGER')->once()->andReturn(null);

        $this->action->handle('NOLONGER');
    }

    public function test_coupon_with_invalid_rules_throws_exception(): void
    {
        $this->expectException(CouponNotValidException::class);

        $this->couponRepository->shouldReceive('find')->with('GBP', 'SAVE20')->once()->andReturn($this->coupon);
        $this->ruleManager->shouldReceive('evaluate')->once()->andReturn(new EvaluationResult(false));

        $this->action->handle('SAVE20');
    }

}