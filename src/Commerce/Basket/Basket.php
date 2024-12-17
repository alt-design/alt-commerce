<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\DiscountItem;

class Basket
{

    /**
     * @param DeliveryItem[] $deliveryItems
     * @param FeeItem[] $feeItems
     * @param LineItem[] $lineItems
     * @param CouponItem[] $coupons
     * @param TaxItem[] $taxItems
     * @param DiscountItem[] $discountItems
     */
    public function __construct(
        public string $id,
        public string $currency,
        public string $countryCode,
        public array $discountItems = [],
        public array $taxItems = [],
        public array $lineItems = [],
        public array $deliveryItems = [],
        public array $feeItems = [],
        public array $coupons = [],
        public int $subTotal = 0,
        public int $taxTotal = 0,
        public int $deliveryTotal = 0,
        public int $discountTotal = 0,
        public int $feeTotal = 0,
        public int $total = 0,
    ) {

    }


}