<?php

namespace AltDesign\AltCommerce\Commerce\PaymentGateway;

use AltDesign\AltCommerce\Contracts\PaymentProvider;

class PaymentProviderRepository implements \AltDesign\AltCommerce\Contracts\PaymentProviderRepository
{
    /**
     * @param PaymentProvider[] $providers
     */
    public function __construct(
        protected array $providers = [],
    )
    {

    }

    public function find(string $name): ?PaymentProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->name() === $name) {
                return $provider;
            }
        }

        return null;

    }

    public function findSuitable(string $country, string $currency): ?PaymentProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports(country: $country, currency: $currency)) {
                return $provider;
            }
        }

        return null;
    }
}