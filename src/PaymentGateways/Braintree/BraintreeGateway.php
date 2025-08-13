<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Commerce\Billing\SubscriptionFactory;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Payment\GenerateAuthTokenRequest;
use AltDesign\AltCommerce\Commerce\Payment\ProcessOrderRequest;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Commerce\Payment\TransactionFactory;
use AltDesign\AltCommerce\Contracts\CustomerRepository;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Exceptions\PaymentFailedException;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use Braintree\Exception\NotFound;
use Braintree\Gateway;
use Braintree\Plan;

class BraintreeGateway implements PaymentGateway
{
    public function __construct(
        protected string $name,
        protected string $currency,
        protected string $merchantAccountId,
        protected TransactionFactory $transactionFactory,
        protected SubscriptionFactory $subscriptionFactory,
        protected Settings $settings,
        protected BraintreeApiClient $client,
        protected CustomerRepository $customerRepository,
    )
    {

    }

    public function processOrder(ProcessOrderRequest $request): Order
    {
        $order = $request->order;

        if (empty($order->billingAddress)) {
            throw new PaymentGatewayException('Billing address is required for braintree');
        }

        $braintreeCustomerId = $this->saveCustomer($order->customerId);
        //$braintreePaymentMethodToken = $this->createPaymentMethod($braintreeCustomerId, $request->gatewayPaymentNonce);

        $this->customerRepository->setGatewayId(
            customerId: $order->customerId,
            gatewayName: $request->gatewayName,
            gatewayId: $braintreeCustomerId,
        );

        if (!empty($order->total)) {
            $result = $this->createCharge(
                billingAddress: $order->billingAddress,
                braintreeCustomerId: $braintreeCustomerId,
                braintreePaymentMethodToken: $request->gatewayPaymentNonce,
                amount: $order->total / 100,
                descriptor: $this->getStatementDescriptor($order->orderNumber)
            );

            $transaction = $this->transactionFactory->createFromGateway(
                driver: 'braintree',
                gateway: $request->gatewayName,
                data: $result->transaction
            );

            $order->transactions[] = $transaction;

            $this->validateTransaction($transaction);
        }

        foreach ($order->billingItems as $item) {

            /*
            $result = $this->createSubscription(
                braintreePaymentMethodToken: $braintreePaymentMethodToken,
                braintreePlanId: $item->getGatewayId($request->gatewayName, ['currency' => $order->currency]),
            );

            $transaction = $this->transactionFactory->createFromGateway(
                driver:'braintree',
                gateway: $request->gatewayName,
                data: $result->subscription->transactions[0]
            );

            $order->transactions[] = $transaction;
            $this->validateTransaction($transaction);

            $order->subscriptions[] = $this->subscriptionFactory->createFromGateway(
                driver:'braintree',
                gateway: $request->gatewayName,
                data: $result->subscription
            );
            */
        }

        return $order;
    }

    public function createPaymentNonceAuthToken(GenerateAuthTokenRequest $request): string
    {
        $params = [
            'merchantAccountId' => $this->merchantAccountId,
        ];
        if (!empty($request->customerId)) {
            $params['customerId'] = $this->saveCustomer($request->customerId);
        }

        // @phpstan-ignore-next-line
        return $this->client->request(fn(Gateway $gateway) => $gateway->clientToken()->generate($params));
    }

    public function saveBillingPlan(BillingPlan $billingPlan): BillingPlan
    {

        $gatewayId = $billingPlan->findGatewayId($this->name,  ['currency' => $this->currency]);

        if (!empty($gatewayId)) {

            try {

                /**
                 * @var Plan $plan
                 */

                $plan = $this->client->request(fn(Gateway $gateway) => $gateway->plan()->find($gatewayId));

                if ((int)$plan->billingFrequency !== $billingPlan->billingInterval->months()) {
                    $gatewayId = null;
                } else {

                    $data = $this->buildBillingPlanData($billingPlan);
                    unset($data['billingFrequency']);

                    $diff = array_filter($data, fn($val, $key) => $plan->{$key} != $val, ARRAY_FILTER_USE_BOTH);
                    if (!empty($diff)) {
                        $this->client->request(fn(Gateway $gateway) => $gateway->plan()->update($gatewayId, $data));
                    }
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

    protected function splitName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        if (empty($fullName)) {
            return [
                'firstName' => null,
                'lastName' => null,
            ];
        }

        $nameParts = explode(' ', $fullName);

        return [
            'firstName' => $nameParts[0],
            'lastName' => count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : null,
        ];
    }

    protected function saveCustomer(string $customerId): string
    {

        $customer = $this->customerRepository->find($customerId) ?? throw new \Exception('Customer not found');

        $id = $this->customerRepository->findGatewayId(customerId: $customerId, gatewayName: $this->name);

        $names = $this->splitName($customer->customerName());

        if (empty($id)) {
            $id = $this->client->request(fn(Gateway $gateway) =>
            $gateway
                ->customer()
                ->create([
                    'firstName' => $names['firstName'],
                    'lastName' => $names['lastName'],
                    'email' => $customer->customerEmail()
                ])
            )->customer->id;

            $this->customerRepository->setGatewayId(
                customerId: $customerId,
                gatewayName: $this->name,
                gatewayId: $id,
            );
        }

        return $id;
    }
    protected function createPaymentMethod(string $gatewayCustomerId, string $paymentNonce): string
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

    protected function createCharge(
        Address $billingAddress,
        string $braintreeCustomerId,
        string $braintreePaymentMethodToken,
        float $amount,
        string|null $descriptor = null

    ): mixed
    {
        $params = [
            'customerId' => $braintreeCustomerId,
           // 'paymentMethodToken' => $braintreePaymentMethodToken,
            'paymentMethodNonce' => $braintreePaymentMethodToken,
            'amount' => $amount,
            'merchantAccountId' => $this->merchantAccountId,
            'options' => [
                'submitForSettlement' => true
            ],
            'billing' => $this->buildAddress($billingAddress),
        ];

        if ($descriptor) {
            // disabled for now
            //$params['descriptor']['name'] = $descriptor;
        }

        return $this->client->request(fn(Gateway $gateway) =>
            $gateway->transaction()->sale($params)
        );
    }

    protected function createSubscription(string $braintreePaymentMethodToken, string $braintreePlanId): mixed
    {
        return $this->client->request(fn(Gateway $gateway) =>
        $gateway->subscription()->create([
            'paymentMethodToken' => $braintreePaymentMethodToken,
            'merchantAccountId' => $this->merchantAccountId,
            'planId' => $braintreePlanId,
            'neverExpires' => true,
            'options' => [
                'startImmediately' => true
            ]
        ])
        );
    }

    protected function validateTransaction(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::FAILED) {
            throw new PaymentFailedException($transaction->rejectionReason ?? 'Unknown transaction failure');
        }
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

    protected function getStatementDescriptor(string $orderNumber): string
    {
        $replacements = [
            '{tradingName}' => $this->settings->tradingName(),
            '{orderNumber}' => $orderNumber
        ];

        $description =  str_replace(array_keys($replacements), array_values($replacements), $this->settings->statementDescriptor());
        return substr($description, 0, 22);
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
            // todo Braintree will throw an exception if the phone number is not valid
            // this needs validating before hand.
            //'phoneNumber' => $address->phoneNumber,
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
