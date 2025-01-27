<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;


use AltDesign\AltCommerce\Support\Money;

class PriceConstraintRule extends BaseRule
{
    public function __construct(
        protected string $currency,
        protected int|null $min = null,
        protected int|null $max = null,
    ) {

    }

    public function handle(): void
    {
        $currency = $this->price()->currency;
        $amount = $this->price()->amount;


        if ($currency !== $this->currency) {
            $this->fail('Currency does not match supplied currency');
            return;
        }

        if ($this->min && ($amount < $this->min)) {
            $this->fail("Supplied amount is less than min amount");
        }

        if ($this->max && ($amount > $this->max)) {
            $this->fail('Supplied amount is greater than max amount');
        }
    }

    protected function price(): Money
    {
        return $this->resolve('price');
    }


}