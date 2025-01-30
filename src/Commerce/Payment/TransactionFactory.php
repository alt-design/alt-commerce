<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use AltDesign\AltCommerce\Support\GatewayEntity;
use Ramsey\Uuid\Uuid;

class TransactionFactory
{
    public function create(mixed $data, string $gateway = null): Transaction
    {
        return match ($gateway) {
            'braintree' => $this->fromBraintreeTransaction($data),
            default => throw new \Exception('Transaction gateway not supported')
        };
        
    }
    
    protected function fromBraintreeTransaction(\Braintree\Transaction $transaction): Transaction
    {
        return new Transaction(
            id: Uuid::uuid4(),
            type: $this->matchType($transaction->type),
            status: $this->matchStatus($transaction->status),
            currency: $transaction->currencyIsoCode,
            amount: intval($transaction->amount) * 100,
            createdAt: \DateTimeImmutable::createFromMutable($transaction->createdAt),
            rejectionReason: $transaction->gatewayRejectionReason,
            additional: $transaction->toArray(),
            gatewayEntities: [
                new GatewayEntity('braintree', $transaction->id)
            ]
        );
    }

    protected function matchType(string $type): TransactionType
    {
        return match($type) {
            'sale' => TransactionType::SALE,
            default =>  throw new PaymentGatewayException("Unknown transaction type $type")
        };
    }

    protected function matchStatus(string $status): TransactionStatus
    {
        return match($status) {
            'authorizing', 'settlement_pending','settling' => TransactionStatus::PENDING,
            'authorization_expired', 'voided', 'settlement_declined',  'failed', 'gateway_rejected', 'processor_declined' => TransactionStatus::FAILED,
            'settled', 'submitted_for_settlement'  => TransactionStatus::SETTLED,
            default =>  throw new PaymentGatewayException("Unknown transaction status $status")
        };
    }
}