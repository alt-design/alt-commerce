<?php

namespace AltDesign\AltCommerce\Support;

final class Price
{
    public readonly string $currency;

    public function __construct(
        public readonly int $amount,
        string $currency,
    ){
        $this->currency = strtoupper($currency);
    }


}