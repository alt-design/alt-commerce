<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Support\GatewayEntity;
use AltDesign\AltCommerce\Traits\HasGatewayEntity;
use DateTimeImmutable;

class Transaction
{
    use HasGatewayEntity;

    /**
     * @param array<string, mixed> $additional
     * @param GatewayEntity[] $gatewayEntities
     */
    public function __construct(
        public string            $id,
        public TransactionType   $type,
        public TransactionStatus $status,
        public string            $currency,
        public int               $amount,
        public DateTimeImmutable $createdAt,
        public string|null       $rejectionReason = null,
        public array             $additional = [],
        public array             $gatewayEntities = [],
    )
    {

    }
}