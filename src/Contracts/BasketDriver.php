<?php

namespace AltDesign\AltCommerce\Contracts;


use AltDesign\AltCommerce\Commerce\Basket\Basket;

interface BasketDriver
{
    public function save(Basket $basket): void;

    public function delete(): void;

    public function get(): Basket;
}