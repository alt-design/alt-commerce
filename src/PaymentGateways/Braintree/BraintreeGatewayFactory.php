<?php

namespace AltDesign\AltCommerce\PaymentGateways\Braintree;


use AltDesign\AltCommerce\Commerce\Billing\SubscriptionFactory;
use AltDesign\AltCommerce\Commerce\Payment\TransactionFactory;
use AltDesign\AltCommerce\Contracts\CustomerRepository;
use AltDesign\AltCommerce\Contracts\PaymentGateway;
use AltDesign\AltCommerce\Contracts\PaymentGatewayFactory;
use AltDesign\AltCommerce\Contracts\Resolver;
use AltDesign\AltCommerce\Contracts\Settings;
use AltDesign\AltCommerce\Exceptions\InvalidConfigException;
use Braintree\Gateway;

class BraintreeGatewayFactory implements PaymentGatewayFactory
{
    protected Resolver $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function create(string $name, string $currency, array $config): PaymentGateway
    {
        $this->validateConfig($config, ['merchant_id', 'public_key', 'private_key', 'mode', 'merchant_accounts']);

        $gateway = new Gateway([
            'environment' => $config['mode'],
            'merchantId' => $config['merchant_id'],
            'publicKey' => $config['public_key'],
            'privateKey' => $config['private_key']
        ]);

        $merchantAccountId = $config['merchant_accounts'][$currency] ?? throw new InvalidConfigException('Merchant account id not specified for '.$currency);

        return new BraintreeGateway(
            name: $name,
            currency: $currency,
            merchantAccountId: $merchantAccountId,
            transactionFactory: $this->resolver->resolve(TransactionFactory::class),
            subscriptionFactory: $this->resolver->resolve(SubscriptionFactory::class),
            settings: $this->resolver->resolve(Settings::class),
            client: new BraintreeApiClient(
                gateway: $gateway
            ),
            customerRepository: $this->resolver->resolve(CustomerRepository::class),
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