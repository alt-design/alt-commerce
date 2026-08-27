<?php

namespace AltDesign\AltCommerce\Tests\Unit\Commerce\Pipeline;

use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Pipeline\ValidateCoupon\ValidateMinimumSpend;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use AltDesign\AltCommerce\Tests\Support\CommerceHelper;
use AltDesign\AltCommerce\Tests\Unit\TestCase;
use Ramsey\Uuid\Uuid;

class ValidateMinimumSpendTest extends TestCase
{
    use CommerceHelper;

    protected ValidateMinimumSpend $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = new ValidateMinimumSpend();
        $this->createBasket();
    }

    protected function addLineItem(int $amount, int $quantity): void
    {
        $this->basket->lineItems[] = new LineItem(
            id: Uuid::uuid4(),
            productId: 'product-1',
            productName: 'Test Product',
            amount: $amount,
            quantity: $quantity,
        );
    }

    public function test_coupon_without_minimum_spend_is_valid(): void
    {
        $coupon = $this->createProductCoupon('10OFF', '10% off', 10, isPercentage: true);

        $this->stage->handle($coupon, $this->basket);

        $this->expectNotToPerformAssertions();
    }

    public function test_coupon_is_valid_when_minimum_spend_is_met(): void
    {
        $coupon = $this->createProductCoupon('10OFF', '10% off', 10, isPercentage: true, minimumSpend: 5000);
        $this->addLineItem(amount: 2500, quantity: 2);

        $this->stage->handle($coupon, $this->basket);

        $this->expectNotToPerformAssertions();
    }

    public function test_coupon_is_not_valid_when_minimum_spend_is_not_met(): void
    {
        $coupon = $this->createProductCoupon('10OFF', '10% off', 10, isPercentage: true, minimumSpend: 5000);
        $this->addLineItem(amount: 2500, quantity: 1);

        $this->expectException(CouponNotValidException::class);
        $this->expectExceptionMessage(CouponNotValidReason::MINIMUM_SPEND_NOT_MET->value);

        $this->stage->handle($coupon, $this->basket);
    }

    public function test_minimum_spend_uses_undiscounted_line_item_totals(): void
    {
        $coupon = $this->createProductCoupon('10OFF', '10% off', 10, isPercentage: true, minimumSpend: 5000);
        $this->addLineItem(amount: 2500, quantity: 2);
        $this->basket->subTotal = 0;

        $this->stage->handle($coupon, $this->basket);

        $this->expectNotToPerformAssertions();
    }
}
