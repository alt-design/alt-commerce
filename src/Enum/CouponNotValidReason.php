<?php

namespace AltDesign\AltCommerce\Enum;

enum CouponNotValidReason: string
{
    case NOT_FOUND = 'not_found';
    case NOT_YET_BEGUN = 'not_yet_begun';
    case EXPIRED = 'expired';
    case NOT_ELIGIBLE = 'not_eligible';
    case MINIMUM_SPEND_NOT_MET = 'minimum_spend_not_met';
}