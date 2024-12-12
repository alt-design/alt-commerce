<?php

namespace AltDesign\AltCommerce\Contracts;

interface DiscountItem
{
    public function name(): string;

    public function amount(): int;

}