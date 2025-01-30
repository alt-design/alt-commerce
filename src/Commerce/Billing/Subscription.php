<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Enum\SubscriptionStatus;
use AltDesign\AltCommerce\Support\GatewayEntity;
use AltDesign\AltCommerce\Traits\HasGatewayEntity;
use DateTimeImmutable;

class Subscription
{
    use HasGatewayEntity;

    /**
     * @param array<string, mixed> $additional
     * @param GatewayEntity[] $gatewayEntities
     */
    public function __construct(
        public string             $id,
        public SubscriptionStatus $status,
        public DateTimeImmutable  $createdAt,
        public array              $additional = [],
        public array              $gatewayEntities = [],
    )
    {
    }
}