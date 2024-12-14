<?php

namespace AltDesign\AltCommerce\Commerce\Payment;

use AltDesign\AltCommerce\Contracts\PaymentProvider;
use AltDesign\AltCommerce\Contracts\SettingsRepository;
use AltDesign\AltCommerce\Exceptions\InvalidConfigException;

class PaymentProviderRepositoryFactory
{

    public function __construct(protected SettingsRepository $settingsRepository)
    {

    }

    /**
     * @param array{enabled: string[], available: array<string, array<string, string>>} $config
     * @return PaymentProviderRepository
     * @throws InvalidConfigException
     */
    public function createFromConfig(array $config): PaymentProviderRepository
    {
        $providers = [];

        $enabled = array_map('trim', $config['enabled']);

        foreach ($config['available'] as $name => $provider) {
            if (!in_array($name, $enabled)) {
                continue;
            }

            $providers[] = $this->createProvider($name, $provider);
        }

        return new PaymentProviderRepository(
            providers: $providers,
        );
    }

    /**
     * @param string $name
     * @param array<string, string> $config
     * @return PaymentProvider
     * @throws InvalidConfigException
     */
    protected function createProvider(string $name, array $config): PaymentProvider
    {
        return match ($config['driver'] ?? null) {
            'braintree' => $this->createBraintreeProvider($name, $config),
            default => throw new InvalidConfigException('Payment provider driver '.$config['driver'].' not supported.'),
        };
    }

    /**
     * @param string $name
     * @param array<string, string> $config
     * @return BraintreePaymentProvider
     * @throws InvalidConfigException
     */
    protected function createBraintreeProvider(string $name, array $config): BraintreePaymentProvider
    {
        $this->validateConfig($config, ['merchant_id', 'public_key', 'private_key', 'mode', 'currency']);

        return new BraintreePaymentProvider(
            settingsRepository: $this->settingsRepository,
            name: $name,
            currency: $config['currency'],
            merchantId: $config['merchant_id'],
            publicKey: $config['public_key'],
            privateKey: $config['private_key'],
            mode: $config['mode'],
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