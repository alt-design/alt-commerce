<?php

namespace AltDesign\AltCommerce\Contracts;

interface Resolver
{
    public function resolve(string $abstract, array $with = []): mixed;
}