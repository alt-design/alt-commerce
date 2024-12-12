<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;

interface Coupon
{
    public function name(): string;

    public function code(): string;

    public function discountAmount(): int;

    public function currency(): string;

    public function discountType(): DiscountType;

    public function ruleGroup(): RuleGroup;

}