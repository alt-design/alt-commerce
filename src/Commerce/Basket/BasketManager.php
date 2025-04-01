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
        $context = $this->context('default');

        if (method_exists($context, $name)) {
            return call_user_func_array([$context, $name], $arguments);
        }

        throw new \BadMethodCallException("Method [$name] does not exist.");
    }
}