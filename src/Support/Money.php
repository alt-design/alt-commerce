<?php

namespace AltDesign\AltCommerce\Support;

final class Money
{
    public readonly string $currency;

    public function __construct(
        public readonly int $amount,
        string $currency
    ){
        $this->currency = strtoupper($currency);
    }


}