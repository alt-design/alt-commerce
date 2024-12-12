<?php

namespace AltDesign\AltCommerce\Contracts;

interface ProductRepository
{
    public function find(string $productId): ?Product;
}