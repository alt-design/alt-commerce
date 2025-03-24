<?php

namespace AltDesign\AltCommerce\Exceptions;

use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use Exception;

class CouponNotValidException extends Exception
{
    public function __construct(public CouponNotValidReason $reason)
    {
        parent::__construct($this->reason->value);
    }
}