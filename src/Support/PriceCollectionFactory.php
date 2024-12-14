<?php

namespace AltDesign\AltCommerce\Support;

use AltDesign\AltCommerce\Contracts\BasketRepository;

class PriceCollectionFactory
{
    public function __construct(protected BasketRepository $basketRepository)
    {

    }

    public function create(Price ...$prices): PriceCollection
    {



        return new PriceCollection(basketRepository: $this->basketRepository, prices: $prices);
    }
}