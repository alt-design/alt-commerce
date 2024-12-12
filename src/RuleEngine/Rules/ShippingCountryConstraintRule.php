<?php

namespace AltDesign\AltCommerce\RuleEngine\Rules;

use AltDesign\AltCommerce\Commerce\Customer\Address;

class ShippingCountryConstraintRule extends BaseRule
{

    /**
     * @param string[] $countryCodes
     */
    public function __construct(
        protected array $countryCodes,
    )
    {

    }

    protected function handle(): void
    {
        if (!in_array($this->shippingAddress()->countryCode, $this->countryCodes)) {
            $this->fail('Shipping address country code is not in supplied country codes');
        }
    }

    protected function shippingAddress(): Address
    {
        return $this->resolve('shippingAddress');
    }

}