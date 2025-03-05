<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;

class UpdateBasketCountryAction
{
    public function __construct(
        protected BasketRepository $basketRepository
    )
    {

    }

    public function handle(string $countryCode): void
    {
        $basket = $this->basketRepository->get();

        if ($basket->countryCode === $countryCode) {
            return;
        }

        $basket->countryCode = $countryCode;
    }
}