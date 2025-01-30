<?php

namespace AltDesign\AltCommerce\Commerce\Basket;

use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\GatewayEntity;
use AltDesign\AltCommerce\Traits\HasGatewayEntity;

class BillingItem
{
    use HasGatewayEntity;

    /**
     * @param array<string, mixed> $additional
     * @param GatewayEntity[] $gatewayEntities
     */
    public function __construct(
        public string $id,
        public string $productId,
        public string $billingPlanId,
        public string $productName,
        public int $amount,
        public Duration $billingInterval,
        public Duration|null $trialPeriod = null,
        public array $additional = [],
        public array $gatewayEntities = []
    )
    {

    }
}