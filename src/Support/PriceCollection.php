<?php

namespace AltDesign\AltCommerce\Support;

use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Exceptions\PriceNotAvailableException;

final class PriceCollection
{
    /**
     * @param Price[] $prices
     */
    public function __construct(protected BasketRepository $basketRepository, public array $prices = [])
    {

    }

    public function currency(string $currency): Price
    {
        foreach ($this->prices as $price) {
            if (strtoupper($price->currency) === $currency) {
                return $price;
            }
        }

        throw new PriceNotAvailableException('Collection does not contain a price for currency '.$currency);
    }

    public function default(): Price
    {
        return $this->currency($this->basketRepository->get()->currency);
    }

    public function supports(string $currency): bool
    {
        foreach ($this->prices as $price) {
            if (strtoupper($price->currency) === $currency) {
                return true;
            }
        }
        return false;
    }


}