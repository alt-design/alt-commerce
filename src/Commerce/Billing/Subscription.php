<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Enum\SubscriptionStatus;

class Subscription
{
    /**
     * @param string $subscriptionId
     * @param string $gateway
     * @param SubscriptionStatus $status
     * @param \DateTimeImmutable $createdAt
     * @param array<string, mixed> $additional
     */
    public function __construct(
        public string $subscriptionId,
        public string $gateway,
        public SubscriptionStatus $status,
        public \DateTimeImmutable $createdAt,
        public array $additional = [],
    )
    {

    }
}