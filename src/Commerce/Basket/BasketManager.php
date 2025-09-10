<?php

namespace AltDesign\AltCommerce\Commerce\Basket;


/**
 * @method string id()
 * @method int total()
 * @method string $currency()
 */
class BasketManager
{
    public function __construct(
        protected BasketBroker $broker,
    )
    {

    }

    public function driver(string $driver, array $config = []): BasketContextFactory
    {
        return new BasketContextFactory(broker: $this->broker, driver: $driver, config: $config);
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