<?php

namespace AltDesign\AltCommerce\Enum;

enum DiscountType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
}