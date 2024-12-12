<?php

namespace AltDesign\AltCommerce\Commerce\PaymentGateway;

use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use DateTimeImmutable;

class Transaction
{
    /**
     * @param TransactionType $type
     * @param TransactionStatus $status
     * @param string $currency
     * @param string $transactionId
     * @param string $gateway
     * @param int $amount
     * @param DateTimeImmutable $createdAt
     * @param string|null $rejectionReason
     * @param array<string, mixed> $additional
     */
    public function __construct(
        public TransactionType $type,
        public TransactionStatus $status,
        public string $currency,
        public string $transactionId,
        public string $gateway,
        public int $amount,
        public DateTimeImmutable $createdAt,
        public string|null $rejectionReason = null,
        public array $additional = [],
    )
    {

    }
}