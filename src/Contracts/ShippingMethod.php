<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;

interface ShippingMethod
{
    public function id(): string;

    public function name(): string;

    public function ruleGroup(): RuleGroup;

    public function currency(): string;

    public function calculatePrice(Basket $basket, Address $address): int;

}