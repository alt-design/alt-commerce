<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Commerce\Billing\SubscriptionFactory;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\CreatePaymentRequest;
use AltDesign\AltCommerce\Commerce\Payment\CreateSubscriptionRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Commerce\Payment\TransactionFactory;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use Braintree\Exception\NotFound;
use Braintree\Gateway;
use Braintree\Plan;

class BraintreeGateway implements PaymentGateway
{
    public function __construct(
        protected string $name,
        protected string $currency,
        protected TransactionFactory $transactionFactory,
        protected SubscriptionFactory $subscriptionFactory,
        protected BraintreeApiClient $client,
    )
    {

    }

    public function createPaymentNonceAuthToken(): string
    {
        return $this->client->request(fn(Gateway $gateway) => $gateway->clientToken()->generate());
    }

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan
    {

        $gatewayId = $billingPlan->findGatewayId($this->name);

        if (!empty($gatewayId)) {

            try {

                /**
                 * @var Plan $plan
                 */

                $updatedAt = $plan->updatedAt;

                // grab plan to see if billing frequency has changes
                $plan = $this->client->request(fn(Gateway $gateway) => $gateway->plan()->find($gatewayId));

                if ((int)$plan->billing_frequency !== $billingPlan->billingInterval->months()) {
                    $gatewayId = null;
                } else {
                    // ensure plan has been changed based on update
                    $data = $this->buildBillingPlanData($billingPlan);
                    unset($data['billingFrequency']);
                    $this->client->request(fn(Gateway $gateway) => $gateway->plan()->update($gatewayId, $data));
                    return $billingPlan;
                }

            } catch (NotFound) {
                // not found so might as well recreate it
                $gatewayId = null;
            }
        }

        if (empty($gatewayId)) {
            $planId = $this->client
                ->request(
                    fn(Gateway $gateway) =>
                        $gateway->plan()->create($this->buildBillingPlanData($billingPlan))
                )
                ->plan->id;

            $billingPlan->setGatewayId($this->name, $planId, ['currency' => $this->currency]);
            $billingPlan->updatedAt = new \DateTimeImmutable();
        }

        return $billingPlan;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveCustomer(Customer $customer, array $data): string
    {
        $id = $customer->findGatewayId($this->name);
        if (empty($id)) {
            $id = $this->client->request(fn(Gateway $gateway) =>
                $gateway
                    ->customer()
                    ->create([
                        'email' => $customer->customerEmail()
                    ])
            )->customer->id;
        }

        return $id;
    }

    public function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string
    {
        $result = $this->client->request(fn(Gateway $gateway) =>
            $gateway->paymentMethod()->create([
                'customerId' => $gatewayCustomerId,
                'paymentMethodNonce' => $paymentNonce,
                'options' => [
                    'verifyCard' => true
                ]
            ])
        );
        return $result->paymentMethod->token;
    }

    public function createCharge(CreatePaymentRequest $request): Transaction
    {
        if (empty($request->billingAddress)) {
            throw new PaymentGatewayException('Billing address is required for braintree');
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

        $result = $this->client->request(fn(Gateway $gateway) =>
            $gateway->transaction()->sale($params)
        );

        return $this->transactionFactory->createFromGateway('braintree', $this->name, $result->transaction);
    }

    public function createSubscription(CreateSubscriptionRequest $request): Subscription
    {
        $result = $this->client->request(fn(Gateway $gateway) =>
            $gateway->subscription()->create([
                'paymentMethodToken' => $request->gatewayPaymentMethodToken,
                'planId' => $request->gatewayPlanId,
                'options' => [
                    'startImmediately' => true
                ]
            ])
        );

        return $this->subscriptionFactory->createFromGateway('braintree', $this->name, $result);
    }

    /**
     * @return array<string,mixed>
     */
    protected function buildBillingPlanData(BillingPlan $billingPlan): array
    {
        $billingFrequencyMonths = match ($billingPlan->billingInterval->unit) {
            DurationUnit::MONTH => $billingPlan->billingInterval->amount,
            DurationUnit::YEAR => $billingPlan->billingInterval->amount * 12,
            default => throw new \Exception('Braintree only supports monthly and yearly billing intervals')
        };

        $data = [
            'name' => $billingPlan->name,
            'billingFrequency' => $billingFrequencyMonths,
            'currencyIsoCode' => $this->currency,
            'price' => $billingPlan->prices->getAmount($this->currency) / 100,
        ];

        if (!!$billingPlan->trialPeriod) {
            $data['trialPeriod'] = true;
            $data['trialDuration'] = $billingPlan->trialPeriod->days() ;
            $data['trialPeriodUnit'] = 'day';
        }

        return $data;

    }

    /**
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