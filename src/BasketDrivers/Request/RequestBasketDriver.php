<?php

namespace AltDesign\AltCommerce\BasketDrivers\Request;


use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;


class RequestBasketDriver implements BasketDriver
{

    protected Basket $basket;

    public function __construct(protected BasketFactory $basketFactory)
    {
        $this->basket = $this->basketFactory->create();
    }

    public function save(Basket $basket): void
    {
        // do nothing
    }

    public function delete(): void
    {
        // do nothing
    }

    public function get(): Basket
    {
        return $this->basket;
    }

}