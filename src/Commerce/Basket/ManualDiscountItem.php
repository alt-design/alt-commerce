<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Contracts\DiscountItem;

class ManualDiscountItem implements DiscountItem
{

    public function __construct(protected string $name, protected int $amount)
    {

    }

    public function name(): string
    {
        return $this->name;
    }

    public function amount(): int
    {
        return $this->amount;
    }
}