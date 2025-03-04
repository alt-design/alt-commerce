<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Support\Money;

class BasketSubTotalConstraintRule extends PriceConstraintRule
{

    public function handle(): void
    {
        $this->setContext('price', new Money($this->basket()->subTotal, $this->basket()->currency));

        parent::handle();
    }

    protected function basket(): Basket
    {
        return $this->resolve('basket');
    }

}