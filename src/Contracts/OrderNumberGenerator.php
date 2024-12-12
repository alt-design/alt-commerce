<?php

namespace AltDesign\AltCommerce\Contracts;

interface OrderNumberGenerator
{
    public function reserve(): string;
}