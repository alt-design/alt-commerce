<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Enum\SubscriptionStatus;
use DateTimeImmutable;

class Subscription
{

    /**
     * @param array<string, mixed> $additional
     */
    public function __construct(
        public string             $id,
        public SubscriptionStatus $status,
        public DateTimeImmutable  $createdAt,
        public array              $additional = [],
        public string|null        $gateway = null,
        public string|null        $gatewayId = null,
    )
    {
    }
}