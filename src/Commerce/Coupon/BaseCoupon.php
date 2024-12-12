<?php

namespace AltDesign\AltCommerce\Commerce\Coupon;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;

abstract class BaseCoupon implements Coupon
{
    public function __construct(
        protected string $name,
        protected string $code,
        protected string $currency,
        protected int $discountAmount,
        protected RuleGroup $ruleGroup,
    ) {

    }

    public function name(): string
    {
        return $this->name;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function discountAmount(): int
    {
        return $this->discountAmount;
    }

    public function currency(): string
    {
        return strtoupper($this->currency);
    }

    public function ruleGroup(): RuleGroup
    {
        return $this->ruleGroup;
    }

}