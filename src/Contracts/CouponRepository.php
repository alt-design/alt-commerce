<?php

namespace AltDesign\AltCommerce\Contracts;

interface CouponRepository
{
    public function find(string $currency, string $code): Coupon|null;
}