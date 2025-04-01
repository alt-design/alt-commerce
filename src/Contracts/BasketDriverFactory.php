<?php

namespace AltDesign\AltCommerce\Contracts;

interface BasketDriverFactory
{
    public function create(Resolver $resolver, array $config): BasketDriver;
}