<?php

namespace AltDesign\AltCommerce\Contracts;

interface Customer
{
    public function customerId(): string;

    public function customerEmail(): string;

    public function findGatewayId(string $gateway): null|string;

    /**
     * @return array<string,mixed>
     */
    public function customerAdditionalData(): array;
}