<?php

namespace AltDesign\AltCommerce\Contracts;

use AltDesign\AltCommerce\Commerce\PaymentGateway\PaymentRequest;
use AltDesign\AltCommerce\Commerce\PaymentGateway\Transaction;

interface PaymentProvider
{

    public function name(): string;

    public function supports(string $country, string $currency): bool;

    /**
     * @param array<string, string> $params
     * @return string
     */
    public function clientToken(array $params = []): string;

    public function attemptPayment(PaymentRequest $request): Transaction;

}