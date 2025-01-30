<?php

namespace AltDesign\AltCommerce\Support;

class GatewayEntity
{
    /**
     * @param array<string, string> $context
     */
    public function __construct(
        public string $gateway,
        public string $gatewayId,
        public array $context = [],
    )
    {

    }
}