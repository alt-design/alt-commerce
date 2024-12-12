<?php

namespace AltDesign\AltCommerce\Commerce\Settings;

readonly class Settings
{
    /**
     * @param string $tradingName
     * @param string $statementDescriptor
     * @param string $defaultCountryCode
     * @param string $defaultCurrency
     * @param array<int, string> $supportedCurrencies
     * @param int $couponLimit
     * @param array<string, string> $braintreeConfiguration
     */
    public function __construct(
        public string $tradingName,
        public string $statementDescriptor = '{tradingName} {orderNumber}',
        public string $defaultCountryCode = 'US',
        public string $defaultCurrency = 'USD',
        public array $supportedCurrencies = ['USD', 'GBP', 'EUR', 'AUD'],
        public int $couponLimit = 1,
        public array $braintreeConfiguration = []
    ) {

    }
}