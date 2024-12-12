<?php

namespace AltDesign\AltCommerce\Commerce\PaymentGateway;

final class ClientToken
{
    public function __construct(public string $token, public string $provider)
    {

    }
}