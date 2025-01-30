<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;


use AltDesign\AltCommerce\Commerce\Billing\SubscriptionFactory;
use AltDesign\AltCommerce\Commerce\Payment\TransactionFactory;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;
use AltDesign\AltCommerce\Exceptions\InvalidConfigException;
use Braintree\Gateway;

class BraintreeGatewayFactory implements PaymentGatewayFactory
{

    /**
     * @param string $currency
     * @param array<string, mixed> $config
     * @return PaymentGateway
     * @throws InvalidConfigException
     */
    public function create(string $name, string $currency, array $config): PaymentGateway
    {
        $this->validateConfig($config, ['merchant_id', 'public_key', 'private_key', 'mode']);

        $gateway = new Gateway([
            'environment' => $config['mode'],
            'merchantId' => $config['merchant_id'],
            'publicKey' => $config['public_key'],
            'privateKey' => $config['private_key']
        ]);

        return new BraintreeGateway(
            name: $name,
            currency: $currency,
            transactionFactory: new TransactionFactory(),
            subscriptionFactory: new SubscriptionFactory(),
            client: new BraintreeApiClient(
                gateway: $gateway
            ),
        );

    }

    /**
     * @param array<string, string> $config
     * @param array<string> $keys
     * @return void
     * @throws InvalidConfigException
     */
    protected function validateConfig(array $config, array $keys): void
    {
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                throw new InvalidConfigException('Payment provider requires '.$key.' to be set.');
            }
        }
    }
}