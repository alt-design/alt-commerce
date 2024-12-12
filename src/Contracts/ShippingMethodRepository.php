<?php

namespace AltDesign\AltCommerce\Contracts;

interface ShippingMethodRepository
{
    /**
     * @return ShippingMethod[]
     */
    public function get(): array;
}