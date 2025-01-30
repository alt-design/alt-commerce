<?php

namespace AltDesign\AltCommerce\Commerce\Billing;

use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerce\Support\GatewayEntity;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerce\Traits\HasGatewayEntity;

class BillingPlan
{

    use HasGatewayEntity;

    /**
     * @param array<string, mixed> $data
     * @param GatewayEntity[] $gatewayEntities
     */
    public function __construct(
        public string          $id,
        public string          $name,
        public PriceCollection $prices,
        public Duration        $billingInterval,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public Duration|null   $trialPeriod = null,
        public array           $data = [],
        public array           $gatewayEntities = [],
    )
    {

    }

    public function relativePrice(string $currency, Duration $interval): int
    {
        $amount = $this->prices->getAmount($currency);
        if ((string)$this->billingInterval === (string)$interval) {
            return $amount;
        }

        $pricePerDay = $amount / $this->billingInterval->days();
        return (int)($pricePerDay * $interval->days());
    }
}