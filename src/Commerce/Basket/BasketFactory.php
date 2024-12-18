<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Settings\Settings;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Contracts\VisitorLocator;
use Ramsey\Uuid\Uuid;

class BasketFactory
{
    protected Settings $settings;

    public function __construct(
        protected VisitorLocator $visitorLocator,
        protected SettingsRepository $settingsRepository,
    )
    {
    }

    public function create(): Basket
    {
        return new Basket(
            id: Uuid::uuid4()->toString(),
            currency: $this->getCurrency(),
            countryCode: $this->getCountry(),
        );
    }

    protected function getCurrency(): string
    {
        if ($location = $this->visitorLocator->retrieve()) {
            $currency = strtoupper($location->currency);
            return in_array($currency, $this->supportedCurrencies()) ? $currency : strtoupper($this->settings()->defaultCurrency);
        }
        return strtoupper($this->settings()->defaultCurrency);
    }

    /**
     * @return array<int, string>
     */
    protected function supportedCurrencies(): array
    {
        return array_map(fn($item) => strtoupper($item), $this->settings()->supportedCurrencies);
    }

    protected function getCountry(): string
    {
        if ($location = $this->visitorLocator->retrieve()) {
            return strtoupper($location->countryCode);
        }
        return strtoupper($this->settings()->defaultCountryCode);
    }

    protected function settings(): Settings
    {
        return $this->settings = $this->settings ?? $this->settingsRepository->get();
    }
}