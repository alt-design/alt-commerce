<?php

namespace AltDesign\AltCommerce\Support;

use AltDesign\AltCommerce\Exceptions\PriceNotAvailableException;

final class PriceCollection
{
    /**
     * @param Price[] $prices
     */
    public function __construct(public array $prices = [])
    {

    }

    public function currency(string $currency): int
    {
        $currency = strtoupper($currency);
        foreach ($this->prices as $price) {
            if (strtoupper($price->currency) === $currency) {
                return $price->amount;
            }
        }

        throw new PriceNotAvailableException('Collection does not contain a price for currency '.$currency);
    }

    public function supports(string $currency): bool
    {
        $currency = strtoupper($currency);
        foreach ($this->prices as $price) {
            if (strtoupper($price->currency) === $currency) {
                return true;
            }
        }
        return false;
    }


}