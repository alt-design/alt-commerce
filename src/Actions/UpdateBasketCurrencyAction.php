<?php

namespace AltDesign\AltCommerce\Actions;

use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Exceptions\CurrencyNotSupportedException;

class UpdateBasketCurrencyAction
{
    public function __construct(
        protected BasketRepository $basketRepository,
        protected SettingsRepository $settingsRepository,
        protected RecalculateBasketAction $recalculateBasketAction
    )
    {

    }

    public function handle(string $currency): void
    {
        $basket = $this->basketRepository->get();

        $currency = strtoupper($currency);

        if ($basket->currency === $currency) {
            return;
        }

        if (!in_array($currency, $this->settingsRepository->get()->supportedCurrencies)) {
            throw new CurrencyNotSupportedException("Currency $currency is not supported");
        }

        // removed line items that do not support new currency
        foreach ($basket->lineItems as $key => $item) {
            if (!$item->product->prices()->supports($currency)) {
                unset($basket->lineItems[$key]);
            }
        }

        $basket->currency = $currency;

        $this->recalculateBasketAction->handle();
    }
}