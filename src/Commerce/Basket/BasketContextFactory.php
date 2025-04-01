<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

class BasketContextFactory
{
    public function __construct(protected BasketBroker $broker, protected string $driver = 'default', protected array $config = [])
    {

    }

    public function context(string $context): BasketContext
    {
        return $this->broker->build($this->driver, $context, $this->config);
    }
}