<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\Basket\Basket;

interface BasketRepository
{
    public function save(Basket $basket): void;

    public function delete(): void;

    public function get(): Basket;

}