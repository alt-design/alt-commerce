<?php

namespace AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\LineDiscount;
use AltDesign\AltCommerce\Contracts\ProductCoupon;
use AltDesign\AltCommerce\Enum\DiscountType;
use Ramsey\Uuid\Uuid;

class CalculateProductCouponsDiscounts
{
    public function __construct()
    {

    }

    public function handle(Basket $basket): void
    {
        foreach ($basket->coupons as $couponItem) {
            if ($couponItem->coupon instanceof ProductCoupon) {
                $this->processCoupon($couponItem->coupon, $basket);
            }
        }
    }

    protected function processCoupon(ProductCoupon $coupon, Basket $basket): void
    {

        if ($basket->subTotal === 0) {
            return;
        }

        $discountTotal = $coupon->isPercentage() ?
            $basket->subTotal * $coupon->discountAmount() / 100 :
            $coupon->discountAmount();


        $discountItem = new DiscountItem(
            id: Uuid::uuid4()->toString(),
            name: $coupon->name(),
            amount: $discountTotal,
            type: DiscountType::PRODUCT_COUPON,
            couponCode: $coupon->code(),
        );

        foreach ($basket->lineItems as $item) {

            if (!$coupon->isProductEligible($item->productId)) {
                continue;
            }

            $item->discounts[] = new LineDiscount(
                id: Uuid::uuid4()->toString(),
                discountItemId: $discountItem->id,
                name: $coupon->name(),
                amount: $discountTotal,
            );

            $item->discountTotal += array_sum(array_column($item->discounts, 'amount'));
        }

        $basket->discountItems[] = $discountItem;

        $basket->subTotal = max($basket->subTotal - $discountTotal, 0);

    }

}