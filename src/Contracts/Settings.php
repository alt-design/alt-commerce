<?php

namespace AltDesign\AltCommerce\Contracts;

interface Settings
{

    public function tradingName(): string;

    public function statementDescriptor(): string;

    public function defaultCountryCode(): string;

    public function defaultCurrency(): string;

    /**
     * @return array<int,string>
     */
    public function supportedCurrencies(): array;


}