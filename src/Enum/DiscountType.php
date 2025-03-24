<?php

namespace AltDesign\AltCommerce\Enum;

enum DiscountType: string
{
    case PRODUCT_COUPON = 'product_coupon';
    case MANUAL = 'manual';
}