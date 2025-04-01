<?php

namespace AltDesign\AltCommerce\Commerce\Basket;


use AltDesign\AltCommerce\Traits\InteractWithBasket;

/**
 * @method int total()
 */
class BasketManager
{
    use InteractWithBasket {
        InteractWithBasket::find as traitFind;
    }

    public function __construct(
        protected BasketBroker $broker,
    )
    {

    }


    public function context(string $context): BasketContext
    {
        return $this->broker->context($context);
    }

    public function __call($method, $parameters)
    {
        $context = $this->context('default');

        if (method_exists($context, $method)) {
            return call_user_func_array([$context, $method], $parameters);
        }

        throw new \BadMethodCallException("Method [$method] does not exist.");
    }
}