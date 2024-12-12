<?php

namespace AltDesign\AltCommerce\Support;

use Exception;

final class PriceCollection
{
    /**
     * @param Price[] $prices
     */
    public function __construct(public array $prices = [])
    {

    }

    public function currency(string $currency): Price
    {
        foreach ($this->prices as $price) {
            if (strtoupper($price->currency) === $currency) {
                return $price;
            }
        }

        throw new Exception('Collection does not contain a price for currency '.$currency);
    }


}