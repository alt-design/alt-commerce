<?php

namespace AltDesign\AltCommerce\Contracts;

interface Customer
{
    public function customerId(): string;

    public function customerEmail(): string;

    public function customerName(): string;

    /**
     * @return array<string,mixed>
     */
    public function customerAdditionalData(): array;
}