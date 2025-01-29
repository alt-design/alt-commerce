<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\CreatePaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\CreateSubscriptionRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use Braintree\Gateway;
use DateTimeImmutable;

class BraintreeGateway implements PaymentGateway
{

    public function __construct(
        protected Gateway $gateway,
        protected string $currency
    )
    {

    }

    public function createPaymentNonceAuthToken(): string
    {
        return $this->gateway->clientToken()->generate();
    }

    public function saveBillingPlan(BillingPlan $billingPlan): string
    {
        $billingFrequencyMonths = match ($billingPlan->billingInterval->unit) {
            DurationUnit::MONTH => $billingPlan->billingInterval->amount,
            DurationUnit::YEAR => $billingPlan->billingInterval->amount * 12,
            default => throw new \Exception('Braintree only supports monthly and yearly billing intervals')
        };

        $existingId = $billingPlan->data['braintree']['ids'][$this->currency] ?? null;
        $data = [
            'name' => $billingPlan->name,
            'billingFrequency' => $billingFrequencyMonths,
            'currencyIsoCode' => $this->currency,
            'price' => $billingPlan->prices->getAmount($this->currency),
            'trialPeriod' => !!$billingPlan->trialPeriod,
            'trialDuration' => $billingPlan->trialPeriod ? $billingPlan->trialPeriod->days() : 0,
            'trialDurationUnit' => 'day',
        ];

        $result = $existingId ? $this->gateway->plan()->update($existingId, $data) : $this->gateway->plan()->create($data);

        return $result->plan->id;

    }

    /**
     * @param Customer $customer
     * @param array<string, mixed> $data
     * @return string
     */
    public function saveCustomer(Customer $customer, array $data): string
    {
        $id = $customer->customerAdditionalData()['braintree_id'] ?? null;

        if (empty($id)) {
            $id = $this->gateway
                ->customer()
                    ->create([
                    'email' => $customer->customerEmail()
                ])
                ->customer
                ->id;
        }

        return $id;
    }

    public function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string
    {
        $result = $this->gateway->paymentMethod()->create([
            'customerId' => $gatewayCustomerId,
            'paymentMethodNonce' => $paymentNonce,
            'options' => [
                'verifyCard' => true
            ]
        ]);

        return $result->paymentMethod->token;
    }

    public function createCharge(CreatePaymentRequest $request): Transaction
    {
        if (empty($request->billingAddress)) {
            throw new PaymentGatewayException('Billing address is required');
        }

        $params = [
            'customerId' => $request->gatewayCustomerId,
            'paymentMethodToken' => $request->gatewayPaymentMethodToken,
            'amount' => $request->amount,
            'options' => [
                'submitForSettlement' => false
            ],
            'billing' => $this->buildAddress($request->billingAddress),
        ];

        if ($request->descriptor) {
            $params['descriptor']['name'] = $request->descriptor;
        }

        $result = $this->gateway
            ->transaction()
            ->sale($params);

        if (!$result->success) {
            throw new PaymentGatewayException('Braintree payment failed with error'.$result->message);
        }

        return new Transaction(
            type: $this->matchType($result->transaction->type),
            status: $this->matchStatus($result->transaction->status),
            currency: $result->transaction->currencyIsoCode,
            transactionId: $result->transaction->id,
            gateway: 'braintree',
            amount: intval($result->transaction->amount) * 100,
            createdAt: DateTimeImmutable::createFromMutable($result->transaction->createdAt),
            rejectionReason: $result->transaction->gatewayRejectionReason,
            additional: $result->transaction->toArray(),
        );
    }

    public function createSubscription(CreateSubscriptionRequest $request): void
    {
        $this->gateway->subscription()->create([
                'paymentMethodToken' => $request->gatewayPaymentMethodToken,
                'planId' => $request->gatewayPlanId,
                'options' => [
                    'startImmediately' => true
                ]
            ]
        );

        // todo return transactions object and subscription object
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

    /**
     * @param Address|null $address
     * @return array<string, string|null>|null
     */
    protected function buildAddress(Address|null $address): array|null
    {
        if (empty($address)) {
            return null;
        }

        return [
            'company' => $address->company,
            'countryCodeAlpha2' => $address->countryCode,
            'firstName' => $address->fullName ? $this->firstName($address->fullName) : null,
            'lastName' => $address->fullName ? $this->lastName($address->fullName) : null,
            'locality' => $address->locality,
            'postalCode' => $address->postalCode,
            'region' => $address->region,
            'streetAddress' => $address->street,
            'phoneNumber' => $address->phoneNumber,
        ];
    }

    protected function firstName(string $fullName): string
    {
        $nameParts = explode(' ', $fullName);
        return $nameParts[0];
    }

    protected function lastName(string $fullName): string
    {
        $nameParts = explode(' ', $fullName);
        array_shift($nameParts);
        return implode(' ', $nameParts);
    }


}