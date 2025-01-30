<?php

namespace AltDesign\AltCommerce\Support;

class GatewayEntity
{
    public function __construct(
        public string $gateway,
        public string $gatewayId,
    )
    {

    }
}