<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

final class ClientToken
{
    public function __construct(public string $token, public string $provider)
    {

    }
}