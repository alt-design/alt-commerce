<?php

namespace AltDesign\AltCommerce\Commerce\Basket;


/**
 * @method int total()
 */
class BasketManager
{
    public function __construct(
        protected BasketBroker $broker,
    )
    {

    }

    public function context(string $context): BasketContext
    {
        return $this->broker->context($context);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->context('default'), $name], $arguments);
    }
}