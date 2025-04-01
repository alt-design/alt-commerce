<?php

namespace AltDesign\AltCommerce\BasketDrivers\Request;

use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;
use AltDesign\AltCommerce\Contracts\BasketDriverFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class RequestBasketDriverFactory implements BasketDriverFactory
{
    public function create(Resolver $resolver, array $config): BasketDriver
    {
        return new RequestBasketDriver(
            basketFactory: $resolver->resolve(BasketFactory::class)
        );
    }
}