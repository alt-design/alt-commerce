<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Enum\SubscriptionStatus;
use Ramsey\Uuid\Uuid;

class SubscriptionFactory
{
    public function createFromGateway(string $driver, string $gateway, mixed $data): Subscription
    {
        return match ($driver) {
            'braintree' => $this->fromBraintreeSubscription($gateway, $data),
            default => throw new \Exception('Subscription gateway not supported')
        };
    }

    protected function fromBraintreeSubscription(string $gateway, \Braintree\Subscription $subscription): Subscription
    {
        $data = $subscription->toArray();
        unset($data['transactions'], $data['paymentMethodToken'], $data['merchantAccountId']);

        return new Subscription(
            id: Uuid::uuid4(),
            status: SubscriptionStatus::from(strtolower($subscription->status)),
            createdAt: \DateTimeImmutable::createFromMutable($subscription->createdAt),
            additional: $data,
            gateway: $gateway,
            gatewayId: $subscription->id,
        );
    }
}