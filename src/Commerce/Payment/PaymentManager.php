<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\PaymentProviderRepository;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;

class PaymentManager
{
    public function __construct(
        protected PaymentProviderRepository $paymentProviderRepository
    )
    {

    }

    public function clientToken(string $currency, string $country): ClientToken
    {
        $provider = $this->paymentProviderRepository->findSuitable(country: $country, currency: $currency);
        if (empty($provider)) {
            throw new PaymentGatewayException("Unable to find suitable payment provider");
        }

        $params = [
            'country' => $country,
            'currency' => $currency,
        ];

        return new ClientToken(token: $provider->clientToken($params), provider: $provider->name());
    }

}