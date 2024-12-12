<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Customer\Address;

interface Customer
{
    public function shippingAddress(): Address|null;

    public function billingAddress(): Address|null;
}