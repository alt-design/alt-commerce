<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use DateTimeImmutable;

class Transaction
{
    /**
     * @param array<string, mixed> $additional
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
        public string|null       $gateway = null,
        public string|null       $gatewayId = null,
    )
    {

    }
}