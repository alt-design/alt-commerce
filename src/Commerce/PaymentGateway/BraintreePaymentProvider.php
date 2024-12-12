<?php

namespace AltDesign\AltCommerce\Commerce\PaymentGateway;

use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Settings\Settings;
use AltDesign\AltCommerce\Contracts\PaymentProvider;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Exceptions\PaymentGatewayException;
use Braintree\Gateway;
use DateTimeImmutable;


class BraintreePaymentProvider implements PaymentProvider
{
    protected Settings $settings;

    public function __construct(
        SettingsRepository $settingsRepository,
    )
    {
        $this->settings = $settingsRepository->get();
    }

    public function clientToken(array $params = []): string
    {
        if (empty($params['currency'])) {
            throw new PaymentGatewayException('currency is required to obtain a client token with BrainTree');
        }

        return $this->gateway($params['currency'])->clientToken()->generate();
    }

    public function supports(string $country, string $currency): bool
    {
        try {
            $this->gateway($currency);
            return true;
        } catch (PaymentGatewayException) {
            return false;
        }
    }

    public function attemptPayment(PaymentRequest $request): Transaction
    {
        $result = $this->gateway($request->currency)
            ->transaction()
            ->sale([
                'orderId' => $request->orderNumber,
                'amount' => $request->total / 100,
                'paymentMethodNonce' => $request->token,
                'options' => [
                    'submitForSettlement' => true
                ],
                'descriptor' => [
                    'name' => $this->getStatementDescriptor($request->orderNumber),
                ],
                'billing' => $this->buildAddress($request->billingAddress),
            ]);

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
            'firstName' => $address->firstName,
            'lastName' => $address->lastName,
            'locality' => $address->locality,
            'postalCode' => $address->postalCode,
            'region' => $address->region,
            'streetAddress' => $address->street,
            'phoneNumber' => $address->phoneNumber,
        ];
    }

    protected function getStatementDescriptor(string $orderNumber): string
    {
        $replacements = [
            '{tradingName}' => $this->settings->tradingName,
            '{orderNumber}' => $orderNumber
        ];

        $description =  str_replace(array_keys($replacements), array_values($replacements), $this->settings->statementDescriptor);
        return substr($description, 0, 22);
    }

    protected function gateway(string $currency): Gateway
    {
        $currency = strtoupper($currency);

        $config = $this->settings->braintreeConfiguration;

        if (empty($config['BRAINTREE_'.$currency.'_ENABLED'])) {
            throw new PaymentGatewayException("Currency $currency is not enabled");
        }

        $required = ['MERCHANT_ID', 'PUBLIC_KEY', 'PRIVATE_KEY'];
        foreach ($required as $key) {
            if (empty($config["BRAINTREE_{$currency}_{$key}"])) {
                throw new PaymentGatewayException("Braintree configuration for currency $currency is missing $key");
            }
        }

        return new Gateway([
            'environment' => $config['BRAINTREE_ENV'] ?? 'sandbox',
            'merchantId' => $config["BRAINTREE_{$currency}_MERCHANT_ID"],
            'publicKey' => $config["BRAINTREE_{$currency}_PUBLIC_KEY"],
            'privateKey' => $config["BRAINTREE_{$currency}_PRIVATE_KEY"],
        ]);
    }



    public function name(): string
    {
        return 'braintree';
    }
}