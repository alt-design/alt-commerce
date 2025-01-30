<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Enum\SubscriptionStatus;
use AltDesign\AltCommerce\Support\GatewayEntity;
use Ramsey\Uuid\Uuid;

class SubscriptionFactory
{
    public function create(mixed $data, string $gateway = null): Subscription
    {
        return match ($gateway) {
            'braintree' => $this->fromBraintreeSubscription($data),
            default => throw new \Exception('Subscription gateway not supported')
        };
    }

    protected function fromBraintreeSubscription(\Braintree\Subscription $subscription): Subscription
    {
        return new Subscription(
            id: Uuid::uuid4(),
            status: SubscriptionStatus::from(strtolower($subscription->status)),
            createdAt: \DateTimeImmutable::createFromMutable($subscription->createdAt),
            additional: $subscription->toArray(),
            gatewayEntities: [
                new GatewayEntity('braintree', $subscription->id)
            ]
        );
    }
}