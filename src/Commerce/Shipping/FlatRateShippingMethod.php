<?php

namespace AltDesign\AltCommerce\Commerce\Shipping;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Contracts\ShippingMethod;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\Support\Money;

class FlatRateShippingMethod implements ShippingMethod
{

    public function __construct(
        protected string    $id,
        protected string    $name,
        protected Money     $price,
        protected RuleGroup $ruleGroup,
    ) {

    }

    public function ruleGroup(): RuleGroup
    {
        return $this->ruleGroup;
    }

    public function calculatePrice(Basket $basket, Address $address): int
    {
        return $this->price->amount;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function currency(): string
    {
        return $this->price->currency;
    }
}