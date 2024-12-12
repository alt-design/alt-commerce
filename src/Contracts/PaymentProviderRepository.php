<?php

namespace AltDesign\AltCommerce\Contracts;

interface PaymentProviderRepository
{
    public function findSuitable(string $country, string $currency): ?PaymentProvider;

    public function find(string $name): ?PaymentProvider;
}